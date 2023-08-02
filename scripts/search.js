/**
 * Search manager for live search in amrayn.com
 *
 * Requires:
 *  - Debug.js
 *  - utils.js
 *  - Analytics.js
 *  - jquery.mark.min.js
 *  - jquery.history.js
 *  - underscore.js
 */
var SearchManager = {
    data : {
        server : "/svc",
        currentQueryWithCore : null,
        currentQueryWithCoreAndPage : null,
        currentCore : null,
        currentRequests : [],
        searchDelayTimer : null,
        searchLabelsDelayTimer : null,
        searchOtherCoresDelayTimer : null,
        analyticsDelayTimer : null,
        corePrimaryFilter : null,
        coreSecondaryFilter : null,
        fullTextFilterListByCore : null,
        filtersUpdated : false,
        searchDelay : 100,
        searchLabelsDelay: 500,
        searchOtherCoresDelay : 2000,
        analyticsDelay : 3000,
        cores : ["quran", "hadith", /*"audio", "books", */"articles"],
        liveSearchList : ["quran", "hadith", "articles"/*, "books"*/],
        coreLabels : {
            "quran" : "Quran",
            "ayah" : "Quran",
            "hadith" : "Hadith",
            //"audio" : "Audio",
            //"books" : "Books",
            "articles" : "Articles"
        },
        synonyms : {
            "koran": "quran",
            "salat": "prayer",
            "namaz": "prayer",
            "salaah": "prayer",
            "taweez": "amulet",
            "tameema": "amulet",
            "tamima": "amulet",
            "niyyah": "intention",
            "niyyat": "intention",
            "niyya": "intention",
            "testify": "testifies",
        },
    },
    setup : function(initialCore) {
        SearchManager.data.currentCore = initialCore;
        SearchManager.updateFilters(initialCore);
        if (typeof getQueryParams().d !== "undefined" && getQueryParams().d !== 'null') {
            $("#primary-filter-list").val(getQueryParams().d);
        }
        if (typeof getQueryParams().s !== "undefined" && getQueryParams().s !== 'null') {
            $("#secondary-filter-list").val(getQueryParams().s);
        }
        $(document).on("click", ".pagination.next,.pagination.prev,.pagination.page", function() {
            SearchManager.executeSearch($(this).attr("data-page"));
            window.scroll(0, 0);
        });

        // let it load so filters can load too
        /*$(document).on("click", ".query-other-core", function() {
            SearchManager.data.filtersUpdated = false;
            SearchManager.data.currentCore = $(this).attr("data-core");
            SearchManager.executeSearch(1, $(this).attr("data-core"));
            return false;
        });*/

        $(document).on("click", ".retry-search-link", function() {
            SearchManager.executeSearch(1);
        });


        $(document).on("change", "#primary-filter-list,#secondary-filter-list", function() {
            SearchManager.data.currentQueryWithCoreAndPage = "";
            SearchManager.executeSearch(1);
        });

        // We turn this off on this page
        $(document).off('keypress', '.search-bar,.secondary-search-bar');
        $(".search-bar").val($(".search-bar.live-search-bar").val());

        var nonLiveBarSearch = function(e) {
            $(".search-bar.live-search-bar").val($(this).val());
            $(".search-bar").not($(this)).val($(this).val()); // For other views
            liveBarSearch();
        }
        var liveBarSearch = function() {
            window.clearTimeout(SearchManager.data.searchDelayTimer);
            SearchManager.data.searchDelayTimer = setTimeout(function () {
                SearchManager.executeSearch(1);
            }, SearchManager.data.searchDelay);
        }

        $(".search-bar:not(.live-search-bar)").on("change", nonLiveBarSearch);
        //$(".search-bar.live-search-bar").on("change", liveBarSearch);

        $(document).on('keypress', '.search-bar.live-search-bar', function(e) {
          if (e.keyCode === 13) {
            window.clearTimeout(SearchManager.data.searchDelayTimer);
            SearchManager.executeSearch(1);
          }
        });
    },
    highlight : function(query) {
        // We searched for exact but we would strip quotes here for highlighting
        var options = {
            "accuracy": "exactly",
            "synonyms": SearchManager.data.synonyms,
            "className": "search-result",
            "separateWordSearch": true
        };
        if (query.length > 2 && query.trim().indexOf("\"") == 0 && query.trim().lastIndexOf("\"") == query.length - 1) {
            query = query.substr(1, query.length - 2);;
            //options.separateWordSearch = false;
        }
        var list = _.filter(query.split(" "), function(item) { return item.length >= 3; });
        $(".search-item-heading,.search-item-text").unmark().mark(list, options);
    },
    Requests : {
        find : function(requestId) {
            var req = _.find(SearchManager.data.currentRequests, function(item) {
                return item.id == requestId;
            });
            return req ? req.request : null;
        },
        remove : function(requestId) {
            var request = SearchManager.Requests.find(requestId);
            if (request) {
                SearchManager.Requests.removeByRequest(request);
            }
        },
        removeByRequest : function(request) {
            if (request) {
                SearchManager.data.currentRequests = _.without(SearchManager.data.currentRequests,
                    _.findWhere(SearchManager.data.currentRequests, {
                        id: request.id
                    })
                );
            }
        },
        abort : function(requestId) {
            var request = SearchManager.Requests.find(requestId);
            if (request) {
                SearchManager.Requests.abortByRequest(request);
            }
        },
        abortByRequest : function(request) {
            if (request) {
                request.xhr.abort();
                SearchManager.Requests.removeByRequest(request);
            }
        },
        add : function(requestId, request) {
            SearchManager.data.currentRequests.push({
                id : requestId,
                xhr : request
            });
        }
    },
    search : function(options) {
        var query = options.query,
            primaryFilter = options.primaryFilter,
            secondaryFilter = options.secondaryFilter,
            page = options.page,
            core = options.core,
            callback=options.callback,
            summaryOnly = options.summaryOnly;
        var filter1Url = primaryFilter === "" || !primaryFilter ? "" : "&f1=" + primaryFilter;
        var filter2Url = secondaryFilter === "" || !secondaryFilter ? "" : "&f2=" + secondaryFilter;
        var requestId = "search-req:" + core + page;
        SearchManager.Requests.abort(requestId); // Abort previous request (null-safe)

        Debug.log("Search ID [" + requestId + "]");
        var fullTextSearchParam = cook("fts") ? "&f&fts=1" : ""; // To handle local cache
        var summaryOnlySearchParam = summaryOnly ? "&sum" : "";
        var summaryOnlyCoreList = [];
        var otherCores = _.without(SearchManager.data.cores, core);
        for (var i = 0; i < otherCores.length; ++i) {
            if (SearchManager.data.liveSearchList.indexOf(otherCores[i]) > -1) {
                summaryOnlyCoreList.push(otherCores[i]);
            }
        }
        var summaryForUrlParam = summaryOnlyCoreList.length > 0 ? "&sf=" + summaryOnlyCoreList.join(",") : "";
        var extra_path = '';
        if (core !== 'hadith' && core !== 'quran' && core !== 'articles') {
          extra_path = "/search/" + core;
        }
        var finalSearchUrl = SearchManager.data.server + extra_path + __svcargs + "&q=" + query + "&p=" + page + filter1Url + filter2Url + fullTextSearchParam + summaryOnlySearchParam + summaryForUrlParam;
        var request = $.ajax({
            url: finalSearchUrl
        }).done(function(resp) {
            callback(core, resp.result, false);
        }).fail(function(resp,e) {
            callback(core, resp.result, true);
        }).always(function() {
            SearchManager.Requests.remove(requestId);
        });
        SearchManager.Requests.add(requestId, request);
    },
    searchLabels : function(core) {
        return;
        var requestId = "search-labels-req:" + core;
        SearchManager.Requests.abort(requestId); // Abort previous request (null-safe)
        var keys = [];
        $(".search-item-heading>a").each(function(){
            keys.push($(this).attr("key"));
        });
        var request = $.ajax({
            url: '/svc/searchlabels/' + core + __svcargs + '_=' + Math.random(),
            type: 'POST',
            timeout: 10000,
            data: {
                'key': keys.join(",")
            },
        }).done(function(resp) {
            SearchManager.renderLabels(JSON.parse(resp));
        }).always(function(resp) {
            SearchManager.Requests.remove(requestId);
        });
        SearchManager.Requests.add(requestId, request);
    },
    renderLabels : function(data) {
        var labelByKeys = data.l;
        for (var i = 0; i < labelByKeys.length; ++i) {
            var labels = labelByKeys[i].l;
            var key = labelByKeys[i].k;
            for (var j = 0; j < labels.length; ++j) {
                $(".search-item-heading>a[key=" + key + "]>.labels").append(labels[j] + "&nbsp;");
            }
        }
        var bookmarks = data.bk;
        for (var i = 0; i < bookmarks.length; ++i) {
            $(".search-item-heading>a[key=" + bookmarks[i] + "]>.labels").append("<span class='fa fa-star bookmarked-label' title='Bookmarked' notooltip> Bookmarked</span>&nbsp;");
        }
    },
    displaySearchCount : function(query, core, count) {
        var coreLabel = SearchManager.data.coreLabels[core];
        var anchorTag = "";
        var baseUrl = core === 'ayah' || core === 'quran' ? '/search/quran' : '/search/' + core;
        if (SearchManager.data.liveSearchList.indexOf(core) > -1) {
          anchorTag = "<a data-core='" + core + "' href='" + baseUrl + "?q=" + query + "' class='query-other-core' rel='nofollow'>";
        } else {
            anchorTag = "<a href='" + baseUrl + "?q=" + query + "' rel='nofollow'>";
        }
        var fullClass = "other-search-type-" + core;
        $(".search-types > div.type2,.search-types > div.type3").find("." + fullClass).remove();
        var newhtml = "<div class='other-search-types-item " + fullClass + "'>" + anchorTag + coreLabel + (!isNaN(count) && count > 0 ? "<span>" + count + "</span>" : "") + "</a></div>";
        if (!isNaN(count) && count > 0) {
            $(".search-types > div.type2 > div.locallist").append(newhtml);
        } else {
            $(".search-types > div.type3 > div.locallist").append(newhtml);
        }

        if ($(".search-types > div.type2 > .locallist").text().trim().length == 0) {
            $(".search-types > div.type2, .search-types > .search-type.afi").hide();
        } else {
            $(".search-types > div.type2, .search-types > .search-type.afi").show();
        }
        if ($(".search-types > div.type3 > .locallist").text().trim().length == 0) {
            $(".search-types > div.type3, .search-types > .search-type.si").hide();
        } else {
            $(".search-types > div.type3, .search-types > .search-type.si").show();
        }
    },
    querySearchCount : function(query, core) {
        if (SearchManager.data.liveSearchList.indexOf(core) === -1) {
            // Legacy search
            SearchManager.LegacySearchManager.searchCount(query, core, function(data) {
                SearchManager.displaySearchCount(query, data.c, data.n);
            $(".afi-spinner").hide();
            });
        } else {
          SearchManager.searchCount(query, core, function(data) {
              SearchManager.displaySearchCount(query, data.c, data.n);
              $(".afi-spinner").hide();
          });
        }
    },
    searchOtherCores : function(query, activeCore) {
    $(".afi-spinner").show();
        $(".search-types > div.type2 > div.locallist > .other-search-types-item > a:not(.query-other-core), .search-types > div.type3 > div.locallist > .other-search-types-item > a:not(.query-other-core)").parent().remove();
        var otherCores = _.without(SearchManager.data.cores, activeCore);
        for (var i = 0; i < otherCores.length; ++i) {
            SearchManager.querySearchCount(query, otherCores[i]);
        }
    },
    updateFilters : function(core) {
       if (!SearchManager.data.filtersUpdated) {
            $(".primary-list-container").html(SearchManager.data.corePrimaryFilter[core]);
            $(".secondary-list-container").html(SearchManager.data.coreSecondaryFilter[core]);
            $(".filter-text-container").html(SearchManager.data.fullTextFilterListByCore[core]);
            $("#full-text-result-chk").prop("checked", (typeof cook("fts") !== "undefined"));

            SearchManager.data.filtersUpdated = true;
        }
    },
    updatePageElements : function(options) {
        var query = options.query,
            page = options.page,
            core = options.core;

        // Page Url
        var pageUrl = "/search/" + core + "?q=" + encodeURIComponent(query);
        if (page > 1) {
            pageUrl += "&p=" + page;
        }
        if ($("#primary-filter-list").length > 0 && $("#primary-filter-list").val() != "") {
            pageUrl += "&d=" + $("#primary-filter-list").val();
        }
        if ($("#secondary-filter-list").length > 0 && $("#secondary-filter-list").val() != "") {
            pageUrl += "&s=" + $("#secondary-filter-list").val();
        }
        if (Utils.supportsHistoryState()) {
            Debug.log("New page url: " + pageUrl);
            window.history.replaceState({}, "", pageUrl);
        }
        // Page title and context
        var pageTitle = query + " - Live Search " + SearchManager.data.coreLabels[core] + " - amrayn";
        document.title = pageTitle;
        $(".context-list > .selected-context").removeClass("selected-context");
        var contextItem = "";
        if ($(".context-menu-" + core + ":visible").length == 0) {
            contextItem = $(".more-menu-link:visible");
        } else {
            contextItem = $(".context-menu-" + core);
        }
        // We set for all views (invisible and menu link that is visible)
        $(".context-menu-" + core).addClass("selected-context");
        contextItem.addClass("selected-context");
    },
    renderResponse : function(options) {
        var query = options.query,
            page = options.page,
            core = options.core,
            resp = options.resp,
            failed = options.failed;
        $(".search-types > div.type2, .search-types > .search-type.afi, .search-types > div.type3, .search-types > .search-type.si").hide();
        $(".search-types > div.type2 > div.locallist > .other-search-types-item > a.query-other-core, .search-types > div.type3 > div.locallist > .other-search-types-item > a.query-other-core").parent().remove();
        $(".search-results,.search-paging").css("opacity", 1);
        $(".search-results").text("");
        $(".search-paging").html("");
        $(".external-search-link").each(function() {
            $(this).attr("href", $(this).attr("data-href-base") + encodeURIComponent(query));
        });
        if (failed) {
            SearchManager.data.currentQueryWithCoreAndPage = query + "/" + core + "/" + page + "/FAILED";
            SearchManager.data.currentQueryWithCore = query + "/" + core + "/FAILED";
            $(".summary").html("Search failed. <a class='retry-search-link'>Click here to retry</a> or try a different keyword<br/><br/>(<b>We are working to make search experience better. <a href='/about'>Learn more</a></b>).");
            // We pretend other core returned 0 as well so we display on side bar
            var otherCores = _.without(SearchManager.data.cores, core);
            for (var i = 0; i < otherCores.length; ++i) {
                if (SearchManager.data.liveSearchList.indexOf(otherCores[i]) > -1) {
                    SearchManager.displaySearchCount(query, otherCores[i], 0);
                }
            }
            $(".search-types > div.type3, .search-types > .search-type.si").show();
            SearchManager.searchOtherCores(query, core);
            SearchManager.updateFilters(core);
            SearchManager.updatePageElements(options);
            Debug.log("Request failed for [" + core + "]");
            return;
        }
        var result = resp;
        var allCount = 0;
        var allTotal = 0;
        var allTimeTaken = 0;
        for (var i = 0; i < result.length; ++i) {
            var count = parseInt(result[i].result.c);
            if (!isNaN(count) && count > 0 && result[i].result.r) {
                allCount += count;
                allTotal += result[i].result.r.length;
                allTimeTaken += parseInt(result[i].result.t);
            }
        }
        // Result and count
        var mainCoreCount = 0;
        var mainCoreTimeTaken = 0;
        var mainTotal = 0;
        var need_filt = false;
        for (var i = 0; i < result.length; ++i) {
            var count = parseInt(result[i].result.c);
            var timeTaken = parseInt(result[i].result.t);
            if (result[i].result.need_filt) {
              need_filt = true; // do not change it after
            }
            if (!isNaN(count) && count > 0 && result[i].result.r && result[i].result.r.length > 0) {
                var items = result[i].result.r;
                SearchManager.displayResults(items, core);
                mainCoreCount += count;
                mainCoreTimeTaken += timeTaken;
                mainTotal += result[i].result.r.length;
            } else if (!isNaN(count) && result[i].core != core) {
                // Count only
                SearchManager.displaySearchCount(query, result[i].core, count);
                if (count > 0) {
                    $(".search-types > div.type2, .search-types > .search-type.afi").show();
                } else {
                    $(".search-types > div.type3, .search-types > .search-type.si").show();
                }
            }

        }
        SearchManager.highlight(query);
        SearchManager.displaySummary(query, page, mainCoreTimeTaken, mainCoreCount, mainTotal, need_filt);
        SearchManager.displayPages(page, mainCoreCount);
        if (core != SearchManager.data.currentCore || !SearchManager.data.filtersUpdated) {
            SearchManager.updateFilters(core);
        }
        SearchManager.updatePageElements(options);
        window.clearTimeout(SearchManager.data.searchLabelsDelayTimer);
        SearchManager.data.searchLabelsDelayTimer = setTimeout(function () {
            SearchManager.searchLabels(core);
        }, SearchManager.data.searchLabelsDelay);

        // We send analytics and search other cores only when actual query changed
        if (SearchManager.data.currentQueryWithCore != query + "/" + core) {
            window.clearTimeout(SearchManager.data.searchOtherCoresDelayTimer);
            SearchManager.data.searchOtherCoresDelayTimer = setTimeout(function () {
                SearchManager.searchOtherCores(query, core);
                SearchManager.data.searchOtherCoresDelayTimer = null;
            }, SearchManager.data.searchOtherCoresDelay);
        }

        // We send analytics when actual query changed (this means we ignore filters and fulltext toggle)
        if (SearchManager.data.currentQueryWithCoreAndPage != query + "/" + core + "/" + page) {
            window.clearTimeout(SearchManager.data.analyticsDelayTimer);
            SearchManager.data.analyticsDelayTimer = setTimeout(function () {
                Analytics.search(query, core, page);
                Analytics.pageView();
                SearchManager.data.analyticsDelayTimer = null;
            }, SearchManager.data.analyticsDelay);
        }
        SearchManager.data.currentQueryWithCore = query + "/" + core;
        SearchManager.data.currentQuery = query;
        SearchManager.data.alternateQuery = null;
        SearchManager.data.alternateQueryName = null;
        if (query.startsWith('"') && query.endsWith('"')) {
          SearchManager.data.alternateQuery = query.substr(1, query.length - 2);
          SearchManager.data.alternateQueryName = "Normal Search";
          SearchManager.data.alternateQueryIcon = "fa-search-plus";
        } else {
          SearchManager.data.alternateQuery = '"' + query + '"';
          SearchManager.data.alternateQueryName = "Phrase Search";
          SearchManager.data.alternateQueryIcon = "fa-quote-left";
        }
        $(".alternate-search-icon").removeClass("fa-search-plus");
        $(".alternate-search-icon").removeClass("fa-quote-left");
        $(".alternate-search-icon").addClass(SearchManager.data.alternateQueryIcon);
        $("#alternate-search-link").attr("href", "/search/" + SearchManager.data.currentCore + "?q=" + SearchManager.data.alternateQuery);
        $("#alternate-search-link").text(SearchManager.data.alternateQueryName);
        if (core === 'hadith' || core === 'quran') {
          $(".local-alternate-search").show();
        } else {
          $(".local-alternate-search").hide();
        }
        SearchManager.data.currentQueryWithCoreAndPage = query + "/" + core + "/" + page;
        SearchManager.data.currentCore = core;
    },
    executeSearch : function(page, core) {
        if (typeof core === "undefined") {
            core = SearchManager.data.currentCore;
        }
        var query = $(".search-bar.live-search-bar").val();
	    if (query.length == 0) return;
	if (query.length < 3) {
            $(".summary").html( 3-query.length + " chars left to start live search");
		return;
	}
        if (SearchManager.data.currentQueryWithCoreAndPage == query + "/" + core + "/" + page) {
            Debug.log("No change [" + core + "]");
            return;
        }
        // Abort all previous requests as this takes lead
        for (var i = 0; i < SearchManager.data.currentRequests.length; ++i) {
            var request = SearchManager.data.currentRequests[i];
            SearchManager.Requests.abortByRequest(request);
        }
        $(".search-results,.search-paging").css("opacity", 0.3);
        $(".summary").html("<span class='loading-search' style='opacity:0.5'><img src='" + STATIC_IMAGES_BASE + "/load-old.gif?v=" + __img_version + "' style='width:16px;height:16px' /></span> Searching...");
        page = parseInt(page);
        NProgress.start();
        SearchManager.search({
            query : query,
            primaryFilter : $("#primary-filter-list").val(),
            secondaryFilter : $("#secondary-filter-list").val(),
            page : page,
            core : core,
            callback :
            function(core, resp, failed) {
                NProgress.done();
                SearchManager.renderResponse({
                    query : query,
                    page : page,
                    core : core,
                    resp : resp,
                    failed : failed
                });
            }
        });
    },
    displaySummary : function(query, page, timeTaken, count, countOnPage, need_filt) {
        var resultNoun = "";
        if (count == 0 || count === null) {
            resultNoun = "No result";
        } else if (count == 1) {
            resultNoun = "1 result";
        } else {
            resultNoun = count + " results";
        }
        if (need_filt) {
          resultNoun += " (trunc.)"
        }
        var escapedQuery = filterXSS(query);
        var resultSummary = resultNoun + " found for '" + escapedQuery + "'";
        if (timeTaken > 0) {
            var timeInSec = timeTaken / 1000;
            resultSummary += " <span style='color:#666'>(" + timeInSec + " seconds)</span>";
        }
        if (need_filt) {
          resultSummary += "<br/><span style='color:#999'>Results are truncated. Use relevant keyword to search all</span>";
        }
        var maxResults = 30;
        var totalPages = Math.ceil(count / maxResults);
        if (totalPages > 1) {
            resultSummary += "<br/>";
            resultSummary += "Displaying " + countOnPage + " on page " + page + " / " + totalPages;
        }
        $(".summary").html(resultSummary);
    },
    displayPages : function(page, count) {
        $(".search-paging").html("");
        var maxResults = 30;
        var totalPages = Math.ceil(count / maxResults);
        var TOTAL_PAGE_TO_DISPLAY = 5;
        var internalPageToDisplay = TOTAL_PAGE_TO_DISPLAY + 2;
        var internalPageToDisplayFloor = Math.floor(internalPageToDisplay / 2);
        var internalPageToDisplayCeil = Math.ceil(internalPageToDisplay / 2);

        var startPage = Math.max(page - internalPageToDisplayFloor, 1);
        var endPage = Math.min(startPage + internalPageToDisplayCeil, totalPages);
        if (startPage > 1 && startPage == endPage - internalPageToDisplayFloor) {
            startPage -= 1;
        }
        if (totalPages > 1) {
            $(".search-paging").append("<a class='pagination prev' data-page='" + (page - 1) + "'>&#8249; Prev</a>");
        }
        if (startPage != endPage) {
            for (var i = startPage; i <= endPage; ++i) {
                if (i == page) {
                    $(".search-paging").append("<span class='pagination current-page'>" + i + "</span>");
                } else {
                    $(".search-paging").append("<a class='pagination page' data-page='" + i + "'>" + i + "</a>");
                }
            }
        }
        if (totalPages > 1) {
            $(".search-paging").append("<span class='pagination-connector' data-prev-page='" + (page - 1) + "'>|</span>");
            $(".search-paging").append("<a class='pagination next' data-page='" + (page + 1) + "'>Next &#8250;</a>");
        }
    },
    displayResults : function(items, core) {
        var resultItems = [];
        for (var i = 0; i < items.length; ++i) {
            var item = items[i];
            var resultItem = $("<div></div>").addClass("search-item");
            var resultHead = $("<div></div>").addClass("search-item-heading");
            resultHead.html("<a href='" + item.l + "' key='" + item.k + "' core='" + core + "'>" + item.ti + " <span class='labels'></span></a>");
            var resultBody = $("<div></div>").addClass("search-item-text");
            resultBody.html(item.tx);
            var resultFoot = $("<div></div>").addClass("result-item-foot");
            resultFoot.html(item.ft);
            resultItem.append(resultHead);
            resultItem.append(resultBody);
            resultBody.append(resultFoot); // Foot is part of body
            resultItems.push(resultItem);
        }
        $(".search-results").append(resultItems);
    },

    searchCount : function(query, core, callback) {
        var requestId = "searchcountonly-req:" + core;
        SearchManager.Requests.abort(requestId); // Abort previous request (null-safe)
        Debug.log("Legacy Search ID [" + requestId + "]");

        var apiBaseUrl = core === 'ayah' || core === 'quran' ? '/api/search/ayah' :  '/api/search/' + core;
        var request = $.ajax({
            url: apiBaseUrl + __svcargs + 'countonly&q=' + query,
            method: 'GET'
        }).done(function(resp) {
            callback(resp);
        }).always(function(resp) {
            SearchManager.Requests.remove(requestId);
        });
        SearchManager.Requests.add(requestId, request);
    },

    LegacySearchManager : {
        searchCount : function(query, core, callback) {
            var requestId = "legacy-search-req:" + core;
            SearchManager.Requests.abort(requestId); // Abort previous request (null-safe)
            Debug.log("Legacy Search ID [" + requestId + "]");
            var request = $.ajax({
                url: '/svc/searchcount/' + core + __svcargs + 'q=' + query,
                method: 'POST'
            }).done(function(resp) {
                callback(resp);
            }).always(function(resp) {
                SearchManager.Requests.remove(requestId);
            });
            SearchManager.Requests.add(requestId, request);
        }
    }
}
