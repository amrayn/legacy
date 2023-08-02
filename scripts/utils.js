/**
 * utils.js
 * Requires jquery-cookie-1.4.1.min.js
 */

var PageInitialized = false;
var IntializePageOnce = function() {
    if (PageInitialized) {
        return;
    }
    PageInitialized = true;
    $(document).on('click', '.bookmarked,.not-bookmarked', function() {
        if (!isLoggedIn) {
            // we always star it as user may not know that he had already starrted it as he is not signed in yet
            var actionParam = "&action=add-bookmark&key=" + $(this).attr("key");
            Utils.transitionPageLoad('/signin?redr=' + encodeURIComponent(window.location.href) + actionParam);
        } else {
            Bookmark.toggleNew($(this));
        }
    });
    $(document).on('click', ".remove-bookmark-small-icon", function() {
        var obj = $("<a></a>").attr("key", $(this).attr("key")).addClass("bookmarked");
        var parent = $(this).parent();
        Bookmark.toggleNew(obj, function() {
            if (obj.hasClass("not-bookmarked")) {
                parent.remove();
            }
        }, true);
    });
    $(document).on('click', 'span.load-more-items', function() {
        fetchMore(fetchMoreIfOnScreen);
    })
    $(document).on('click', 'a', function(e){
        var eventableTypes = ['.pdf', '.mp3'];
        var sentAnalytics = false;
        for (var i = 0; i < eventableTypes.length; ++i) {
            if (endsWith(this.href, eventableTypes[i])) {
                Analytics.resource(this.href, function() {
                    sentAnalytics = true;
                });
                break;
            }
        }
        return true;
    });
    $(document).on('click', 'a.local', function(e){
        if(e.which != 2 && $(this).attr('target') != '_blank') {
            e.preventDefault();
            Utils.fastPageLoad(this.href, true);
            return false;
        }
    });

    $(document).on('click', 'a.transition', function(e){
        if(e.which != 2 && $(this).attr('target') != '_blank') {
            e.preventDefault();
            Utils.transitionPageLoad(this.href, true);
        }
        return false;
    });
    initializePage = function() {
        $('.more-menu,dropdownmenu').css('display','none');
        if ($(".context-link.selected-context:visible").length == 0 && $(".more-context-link.context-list-a-hover").length) {
            $(".more-menu-link").addClass("selected-context");
        } else {
            $(".more-menu-link").removeClass("selected-context");
        }
        Utils.buildTooltips();
    }
    $(document).ready(function() {
        initializePage();
        $(window).scroll(function () {
            if ($(this).scrollTop() > 220) {
                $('#scroll-to-top').fadeIn();
            } else {
                $('#scroll-to-top').fadeOut();
            }
        });
        $('#scroll-to-top').click(function () {
            $('html, body').animate({ scrollTop: 0 }, 400);
            return false;
        });
        bindMenu('.more-menu-link', '.more-menu', '.more-menu-link', '.more-menu', 'context-list-a-hover');
        // no longer supported
        /*$.ajax({
            url: '/svc/uc' + __svcargs
        }).success(function(data) {
            Utils.renderNewAudiosCount(data);
            Utils.renderNewBlogsCount(data);
            Utils.renderNewBooksCount(data);
        });
*/

        // donation message
        window.setTimeout(function() {
          return; // disabled donation message
          var totalMessageShownCount = window.localStorage.getItem('__donation-msg') || 0;

          if (totalMessageShownCount % 5 === 0 && totalMessageShownCount <= 50) {
            Utils.notifyMessage({
              text: 'We\'re accepting donations. Go to <b><a style=\'color:inherit\' href=\'https://amrayn.com/contribute\'>contribution page</a></b> to find out more<br/><br/><b><a style=\'color:inherit\' href=\'https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=JAJ4G7NJU93MA&source=url\'>Click here to donate now</a></b>',
              timeout: 15000,
              type: 'warning',
            });
            Analytics.send('Donation', 'Message_Displayed');
          }
          window.localStorage.setItem('__donation-msg', ++totalMessageShownCount);
        }, 2000);
    });
}
// Example usage: bindMenu('.more-menu-link', null, '.more-menu-link', '.more-menu', 'context-list-a-hover');
bindMenu = function(linkSelector, menuSelector, linkClass, menuClass, iconHoverClassName) {
    $(linkSelector).click(function() {
        $('body').click(function(e) {
            var targetIsLink = $(e.target).hasClass(linkClass.substr(1));
            var targetIsInMenu = $(menuClass).hasUsingIs($(e.target)) == true || $(menuClass).hasUsingIs($(e.target)).length >= 0;
            var targetIsInLink = $(linkClass + ':visible').hasUsingIs($(e.target)) == true || $(linkClass + ':visible').hasUsingIs($(e.target)).length >= 0;
            var targetToOpenMenu = targetIsLink || targetIsInMenu || targetIsInLink;
            if (!targetToOpenMenu) {
                $(menuClass).fadeOut(200);
                $(linkSelector).removeClass(iconHoverClassName);
            }
        });
        var menu = menuSelector === null ? $(this).next() : $(menuSelector);
        var menuWidth = parseInt(menu.css('width').match(/(\d+)/)[0]);
        var iconPadding = parseInt($(this).css('padding-left').match(/(\d+)/)[0]) + parseInt($(this).css('padding-right').match(/(\d+)/)[0]);
        var menuBorderWidth = parseInt(menu.css('border-left-width').match(/(\d+)/)[0]) + parseInt(menu.css('border-right-width').match(/(\d+)/)[0]);
        var newLeft = ($(this).offset().left + $(this).width() + iconPadding) - menuWidth - menuBorderWidth;
        if (newLeft < 0) {
            menu.css({
                "width" : "100%",
                "left" : "0"
            });
        } else {
            menu.css('left', newLeft);
        }
        if (menu.is(":visible")) {
            $(menuClass).fadeOut(200);
            $(linkSelector).removeClass(iconHoverClassName);
        } else {
            $(linkSelector).addClass(iconHoverClassName);
            menu.fadeIn(200);
        }
    });
}

$.fn.extend({
    hasUsingIs: function(target) {
        if (this.has(target).length > 0) {
            return target;
        }
        for (var i = 0; i <= this.length; ++i) {
            var elem = $(this[i]);
            if (elem.is(target)) {
                return elem;
            }
        }
        return false;
    },
    scrollTo: function(elem) {
        $(this).scrollTop($(this).scrollTop() - $(this).offset().top + $(elem).offset().top);
        return this;
    },
    serializeObject: function(nullInsteadOfEmpty) {
        nullInsteadOfEmpty = typeof nullInsteadOfEmpty === "boolean" ? nullInsteadOfEmpty : false;
        emptyValue = nullInsteadOfEmpty ? null : "";
        var o = {};
        var a = this.serializeArray();
        $.each(a, function() {
            if (o[this.name] !== undefined) {
                if (!o[this.name].push) {
                    o[this.name] = [o[this.name]];
                }
                o[this.name].push(this.value || emptyValue);
            } else {
                o[this.name] = this.value || emptyValue;
            }
        });
        return o;
    },
    isOnScreen : function(){

        var win = $(window);

        var viewport = {
            top : win.scrollTop(),
            left : win.scrollLeft()
        };
        viewport.right = viewport.left + win.width();
        viewport.bottom = viewport.top + win.height();

        var bounds = this.offset();
        bounds.right = bounds.left + this.outerWidth();
        bounds.bottom = bounds.top + this.outerHeight();

        return (!(viewport.right < bounds.left || viewport.left > bounds.right || viewport.bottom < bounds.top || viewport.top > bounds.bottom));
    }
});
var ScreenSize = {
    large: function() {return $(window).width() > 860;},
    medium: function() {return $(window).width() <= 860;},
    small: function() {return $(window).width() <= 500;},
    tiny: function() {return $(window).width() <= 300;},
    atleastMedium: function() {return $(window).width() > 500;},
    atleastSmall: function() {return $(window).width() > 300;},
    get: function() {return $(window).width();}
};
var Utils = {

    addFormError : function(elem, text) {
        Utils.removeFormError(elem);
        elem.parent().append("<div class='errors'>" + text + "</div>");
    },
    removeFormError : function(elem) {
        elem.parent().find(".errors").remove();
    },
    isMobileSafari : function() {
        return navigator.userAgent.match(/(iPod|iPhone|iPad)/) && navigator.userAgent.match(/AppleWebKit/)
    },
    threadSleep : function(seconds)
    {
        var e = new Date().getTime() + (seconds * 1000);
        while (new Date().getTime() <= e) {}
    },
    openEmbedCodeDialog : function(contents) {
        $('#embedCodeDialog').remove();
        $('<div id="embedCodeDialog" title="Embed Code">' + contents + '</div>').appendTo('body');
        $("#embedCodeDialog").dialog({
            width: '100%',
            position: ['cetenr', 20],
            modal: true,
            open: function() {
                jQuery('.ui-widget-overlay').bind('click', function() {
                    jQuery('#embedCodeDialog').dialog('close');
                })
            },
            close: function(event, ui) {
                $("#embedCodeDialog").hide();
            }
        });
    },
    generateToc : function(parentSelector) {
        var newLine, el, title, link;
        var headers = $(parentSelector + " h1" + ", " + parentSelector + " h2" + ", " + parentSelector + " h3");
        if (headers.length < 5) {
            return "";
        }
        var currentTag = "";
        var id_idx = 1;
        var chap = 1;
        var topic = 0;
        var heading = 0;
        var toc = "<div class='toc-container'><nav role='navigation' class='table-of-contents'><div class='toc-head'><span class='toc-head-text'>Contents</span> <span class='toc-toggle'>[Show]</span></div><ul class='toc-contents'>";

        headers.each(function() {
            el = $(this);
            title = el.text();

            if (title.trim().length > 0) {
                if (typeof el.attr("id") === "undefined") {
                    el.attr("id", "head-" + id_idx);
                }
                var nextTag = el.get(0).tagName;
                if (currentTag == "H1" && nextTag == "H2") {
                    toc += "<ul>";
                    topic = 1;
                    heading = 0;
                } else if (currentTag == "H2" && nextTag == "H3") {
                    toc += "<ul>";
                    heading = 1;
                } else if (currentTag == "H3" && nextTag == "H2") {
                    toc += "</ul>";
                    topic++;
                    heading = 0;
                } else if (currentTag == "H2" && nextTag == "H1") {
                    toc += "</ul>";
                    chap++;
                    topic = 0;
                    heading = 0;
                } else if (currentTag == "H3" && nextTag == "H1") {
                    toc += "</ul></ul>";
                    chap++;
                    topic = 0;
                    heading = 0;
                } else if (currentTag == "H2" && nextTag == "H2" && $(parentSelector + " h1").length == 0) {
                    chap++;
                } else if (currentTag == "H3" && nextTag == "H3" && $(parentSelector + " h1").length == 0 && $(parentSelector + " h2").length == 0) {
                    chap++;
                } else if (currentTag == "H1" && nextTag == "H1") {
                    chap++;
                } else if (currentTag == "H2" && nextTag == "H2" && $(parentSelector + " h1").length > 1) {
                    topic++;
                } else if (currentTag == "H3" && nextTag == "H3" && $(parentSelector + " h1").length > 1 && $(parentSelector + " h2").length > 1) {
                    heading++;
                }

                currentTag = el.get(0).tagName;
                link = "#" + el.attr("id");
                var idxStr = chap;
                if (topic > 0) {
                    idxStr += "." + topic;
                }
                if (heading > 0) {
                    idxStr += "." + heading;
                }
                toc += "<li><a href='" + link + "' class='toc-link'>" + idxStr + "&nbsp;&nbsp;&nbsp;" + title + "</a></li>";
                id_idx++;
            }
        });
        toc += "</ul></nav></div>";
        $(parentSelector).find(".toc-placeholder").html(toc);
        // Bind hide/show toc
        $(document).on("click", ".toc-toggle", function() {
            var thisRef = $(this);
            $(".toc-contents").toggleNew("fast", function() {
                thisRef.text($(".toc-contents").is(":visible") ? "[Hide]" : "[Show]");
                if ($(".toc-contents").is(":visible")) {
                    $(parentSelector).find(".toc-container.sticky").css("opacity", 1);
                } else {
                    $(parentSelector).find(".toc-container.sticky").css("opacity", 0.9);
                }
            });
        });
    },
    buildTooltips : function() {
        if (ScreenSize.large()) {
            $('*[title]:not([notooltip])').tooltip({
                animation: true,
                placement:'bottom'
            });
        }
    },
    updateSelectedBreadcrumb : function(label) {
        $(".breadCrumbSelected").text(label);
    },
    updateBreadcrumb : function(name, href, label) {
        $(".breadCrumb" + name + ">span").text(label);
        $(".breadCrumb" + name).attr("href", href);
    },
    bindInfiniteScroll : function(selector, fetchFn) {
        $(window).scroll(function() {
            if ($(selector + ">div:last").position() !== undefined
                && $(selector + ">div:last").position().top - $(window).scrollTop() <= 800) {
                fetchFn();
            }
        });
    },
    bindAudioLoader : function(identifier, csvOrId, type, callback, categoryCsv) {
        onloads[onloads.length++] = function() {
            Utils.pullAudio(identifier, csvOrId, type, null, callback, false, false, categoryCsv);
        }
    },
    ajaxPull : function(url, containerName, identifier, keepElement, callback, retryFn) {
        if (typeof keepElement === "undefined") {
            keepElement = false;
        }
        var spinnerId = "more-" + containerName + "-loading_" + identifier;
        var timerId = "checker_more-" + containerName + "-loading_" + identifier;
        var ajaxId = "ajax_" + identifier;
        var containerId = containerName + "_" + identifier;
        var headId = containerName + "_" + identifier + "_head";

        if (url == "") {
            if (typeof callback === "function") {
                callback();
            } else {
                if ($("#" + containerId + " > div").length === 0) {
                    if (!keepElement) {
                        $("#" + headId).remove();
                        $("#" + containerId).remove();
                    } else {
                        $("#" + containerId).text("Nothing to see here. It may be that all the items are private for this section.");
                    }
                }
            }
            return;
        }

        if ($("#" + spinnerId).length > 0) {
            if ($("#" + spinnerId).find(".retry-loading").length > 0) {
                if ($(document).data(ajaxId) != null && $(document).data(ajaxId) != undefined) {
                    $(document).data(ajaxId).abort();
                    $(document).data(ajaxId, null);
                }
                $("#" + spinnerId).find(".retry-loading").remove();
            } else {
                return;
            }
        }

        if ($("#" + spinnerId).length == 0) {
            $("#" + containerId).append("<center id='" + spinnerId + "'><br><div class='spinner'><div class='spinner-icon'></div></div></center>");
        }
        if ($(document).data(timerId) != undefined && $(document).data(timerId) != null) {
            clearInterval($(document).data(timerId));
            $(document).data(timerId, null);
        }
        $(document).data(timerId, setInterval(function() {
            if ($("#" + spinnerId).find(".retry-loading").length == 0) {
                $("#" + spinnerId).append("<span class='retry-loading'>Taking too long? </span><a class='retry-loading' style='cursor:pointer;'>Click here to retry</a>");
                $("#" + spinnerId).find("a.retry-loading").click(function() {
                    retryFn();
                });
            }
        }, 3000));
        $(document).data(ajaxId,
            $.ajax({
                url: url
            }).success(function(resp) {
                if (resp.data.length > 0) {
                    $("#" + containerId).append(resp.data);
                } else if ($("#" + containerId + " > div").length === 0) {
                    // careful with this as resp.last will cause it to remove the contents
                    if (!keepElement) {
                        $("#" + headId).remove();
                        $("#" + containerId).remove();
                    } else {
                        $("#" + containerId).text("Nothing to see here.");
                    }
                }
                $("#" + spinnerId).remove();
                Bookmark.checkOnPage();
                clearInterval($(document).data(timerId));
                $(document).data(timerId, null);
                $(document).data(ajaxId, null);
                if (typeof callback === "function") {
                    callback(resp);
                }
            })
        );
    },
    pullAudio: function(identifier, csvOrId, type, page, callback, keepElement, linkToBookmarkPos, categoryCsv, breakCache) {
        if (typeof linkToBookmarkPos === "undefined") {
            linkToBookmarkPos = false;
        }
        if (typeof breakCache === "undefined") {
            breakCache = false;
        }
        if (typeof categoryCsv === "undefined") {
            categoryCsv = "";
        }
        var extraParams = "";
        if (breakCache) {
            extraParams += "&cb=" + Math.random();
        }
        var url = "";
        if (csvOrId != "") {
            url = '/svc/fl?i=' + csvOrId + '&t=' + type + __svcargs_mid + "av=" + AUDIO_VERSION + extraParams + (page === null ? "" : "&p=" + page) + (linkToBookmarkPos ? "&poslink" : "") + (categoryCsv != "" ? "&c=" + categoryCsv : "");
        }
        Utils.ajaxPull(url, "lectureitems", identifier, keepElement, callback, function() {
            Utils.pullAudio(identifier, csvOrId, type, page, callback, keepElement, linkToBookmarkPos);
        });
    },
    pullBlogs: function(identifier, extraParams, type, page, callback, keepElement, linkToBookmarkPos) {
        if (typeof linkToBookmarkPos === "undefined") {
            linkToBookmarkPos = false;
        }
        var extraParamsStr = extraParams == null ? "" : extraParams;
        var urlName = linkToBookmarkPos ? "posfb" : "fb";
        var url = '/svc/' + urlName + '?type=' + type + extraParamsStr + '&bv=' + BLOG_VERSION + (page === null ? "" : "&p=" + page) + (linkToBookmarkPos ? "&l=" + __usid : "");
        Utils.ajaxPull(url, "blogitems", identifier, keepElement, callback, function() {
            Utils.pullBlogs(identifier, extraParams, type, page, callback, keepElement, linkToBookmarkPos);
        });
    },
    pullBooks: function(identifier, extraParams, type, page, callback, keepElement) {
        var extraParamsStr = extraParams == null ? "" : extraParams;
        var url = '/svc/fbk?type=' + type + extraParamsStr + __svcargs_mid + 'bv=' + BOOK_VERSION + (page === null ? "" : "&p=" + page);
        Utils.ajaxPull(url, "bookitems", identifier, keepElement, callback, function() {
            Utils.pullBooks(identifier, extraParams, type, page, callback, keepElement);
        });
    },
    popupHelp : function(element, title, details, relativeLeftAdjustment, relativeTopAdjustment) {
        $("#popup-help,#popup-help-container").remove();
        if (element.length == 0) {
            return;
        }
        var holeWidth = 50;
        var holeHeight = 50;
        var holeTop = (element.offset().top + element.height());
        holeTop -= (holeHeight / 2);
        if (typeof relativeTopAdjustment !== "undefined") {
            holeTop += relativeTopAdjustment;
        }
        var holeLeft = (element.offset().left + (element.width() / 2));
        holeLeft -= (holeWidth / 2);
        if (typeof relativeLeftAdjustment !== "undefined") {
            holeLeft += relativeLeftAdjustment;
        }
        var holeCss = {
            "position": "absolute",
            "top": holeTop + "px",
            "left": holeLeft + "px",
            "width": holeWidth + "px",
            "height": holeHeight + "px",
            "box-shadow": "0 0 0 99999px rgba(0, 0, 0, 0.95)",
            "border-radius": "50%",
            "z-index": "2000"
        };
        $("<div></div>").attr("id", "popup-help").css(holeCss).appendTo("body");
        var textCssBase = {
            "color": "#fff",
            "z-index": "2000"
        };
        var helpContainerLeft = $(document).width() < 550 ? Math.min(50, holeLeft) : holeLeft;
        var textContainerCssBase = $.extend({
            "display": "inline",
            "top" : (holeTop + holeHeight) + "px",
            "left" : helpContainerLeft + "px",
            "position": "absolute"
        }, textCssBase);
        var textTitleCssBase = $.extend({
            "font-size" : "30px",
            "font-weight" : "bold",
        }, textCssBase);
        var textTextCssBase = $.extend({
            "font-size" : "20px",
            "display" : "block"
        }, textCssBase);
        $("*").addClass("popup-disabled");
        $("<div></div>").attr("id", "popup-help-container").css(textContainerCssBase).appendTo("body");
        $("<br/>").css(textTitleCssBase).html(title).appendTo("#popup-help-container");
        $("<div></div>").css(textTitleCssBase).html(title).appendTo("#popup-help-container");
        $("<br/>").css(textTitleCssBase).html(title).appendTo("#popup-help-container");
        $("<div></div>").css(textTextCssBase).html(details).appendTo("#popup-help-container");
        $("<br/>").css(textTitleCssBase).html(title).appendTo("#popup-help-container");
        $("<input>").attr({
            "type": "button",
            "value" : "Got It!"
        }).addClass("taskbtn").appendTo("#popup-help-container").click(function() {
            $("#popup-help,#popup-help-container").remove();
            $(".popup-disabled").removeClass("popup-disabled");
        });
    },
    notifyMessage: function(options) {
        Utils.notifyUser(options);
    },
    notifyUser: function(options) {
        var text = options.text;
        var type = options.type; // 'error' or 'success'
        var timeout = options.timeout;
        if (ScreenSize.small()) {
            notyfy({text:text, type: type, timeout: timeout});
        } else {
            alertify.log(text, type, timeout);
        }
    },
    readAllCss : function(jQueryObject) {
        var sheets = document.styleSheets, o = {};
        for (var i in sheets) {
            var rules = sheets[i].rules || sheets[i].cssRules;
            for (var r in rules) {
                if (jQueryObject.is(rules[r].selectorText)) {
                    o = $.extend(o, Utils.css2json(rules[r].style), Utils.css2json(jQueryObject.attr('style')));
                }
            }
        }
        return o;
    },
    css2json : function(css) {
        var s = {};
        if (!css) return s;
        if (css instanceof CSSStyleDeclaration) {
            for (var i in css) {
                if ((css[i]).toLowerCase) {
                    s[(css[i]).toLowerCase()] = (css[css[i]]);
                }
            }
        } else if (typeof css == "string") {
            css = css.split("; ");
            for (var i in css) {
                var l = css[i].split(": ");
                s[l[0].toLowerCase()] = (l[1]);
            }
        }
        return s;
    },
    warnSelfXss : function() {
        if (typeof console === "object" && typeof console.log === "function") {
            console.log("%cWARNING", "font: 2em sans-serif; color: yellow; background-color: red;");
            console.log("%cDo not copy/paste anything here if you do not understand. Even if someone ask you to. Otherwise you may become victim of Self-XSS attack.", "font: 1.5em sans-serif; color: red; background-color: yellow;");
        }
    },
    generalHouseKeeping : function(rand, threshold, cookieName, url) {
        var lastHousekeeping = cook(cookieName, null);
        if (lastHousekeeping !== null) {
            lastHousekeeping = new Date(lastHousekeeping);
        }
        if (lastHousekeeping === null || (new Date()) - lastHousekeeping > threshold) {
            $.ajax({
                url:url + '?=' + rand,
                type:'POST',
                async:true
            });
            saveCookie(cookieName, new Date());
        }
    },
    housekeeping : function(rand) {

    },
    renderNewBlogsCount : function(data) {
        $(".context-menu-articles>.tab-updates-count").remove();
        var total = data.buc;
        if (total > 0) {
            var displayTotal = total;
            if (total >= 100) {
                displayTotal = "99+";
            }
            var tooltip = total + " new article" + (total > 1 ? "s" : "");
            $(".context-menu-articles").append("<span class='tab-updates-count' style='display:none' notooltip title='" + tooltip + "'>" + displayTotal + "</span>");
            $(".tab-updates-count").not(".selected-context>.tab-updates-count").show('fast', 'linear');
        }
    },
    renderNewAudiosCount : function(data) {
        $(".context-menu-audio>.tab-updates-count").remove();
        var actualTotal = data.nso + data.nls + data.nse;
        if (actualTotal > 0) {
            var displayTotal = actualTotal;
            if (actualTotal >= 100) {
                displayTotal = "99+";
            }
            var newAudios = data.nso + data.nls;
            var newAudiosToolTip = newAudios > 0 ? newAudios + " new audio" : "";
            if (newAudios > 1) {
                newAudiosToolTip += "s";
            }
            var newSeriesToolTip = data.nse > 0 ? data.nse + " new series" : "";
            var tooltip = newAudiosToolTip + (newAudiosToolTip != "" && newSeriesToolTip != "" ? " and " : "") + newSeriesToolTip;
            $(".context-menu-audio").append("<span class='tab-updates-count' style='display:none' notooltip title='" + tooltip + "'>" + displayTotal + "</span>");
            $(".tab-updates-count").not(".selected-context>.tab-updates-count").show('fast', 'linear');
        }
    },
    renderNewBooksCount : function(data) {
        $(".context-menu-books>.tab-updates-count").remove();
        var total = data.nbc;
        if (total > 0) {
            var displayTotal = total;
            if (total >= 10) {
                displayTotal = "9+";
            }
            var tooltip = total + " new book" + (total > 1 ? "s" : "");
            $(".context-menu-books").append("<span class='tab-updates-count' style='display:none' notooltip title='" + tooltip + "'>" + displayTotal + "</span>");
            $(".tab-updates-count").not(".selected-context>.tab-updates-count").show('fast', 'linear');
        }
    },
    selectContext : function(context) {
        currentContextJson = context;
        $(".context-list > a.selected-context").removeClass("selected-context");
        $(".more-menu-link").removeClass("selected-context");
        if (currentContextJson !== null) {
            $(".context-list > a.context-menu-" + currentContextJson.id).addClass("selected-context");
            if (currentContextJson.poml) {
                $(".context-list.large > .more-menu-link").addClass("selected-context");
            }
            if (currentContextJson.pomm) {
                $(".context-list.medium > .more-menu-link").addClass("selected-context");
            }
            if (currentContextJson.poms) {
                $(".context-list.small > .more-menu-link").addClass("selected-context");
            }
        }
    },
    supportsHistoryState : function() {
        return typeof history === "object" && typeof history.pushState === "function" && typeof history.replaceState === "function";
    },
    ajaxPageLoad : function(url, pushState) {
        // Mixed content error occurs in some cases so we override all requests!
        Utils.transitionPageLoad(url);
        return;

        var responseStart = (new Date()).getTime();
        // By default we are not on search page (search page can re-set this variable any way)
        $.data(document, 'onSearchPage', false);
        // We want to stop soundManager
        if ($.data(document, 'soundManagerReady')) {
            soundManager.stopAll();
        }
        // We want to cancel any audio/video loading
        var avTags = $("audio,video");
        for (var i = 0; i < avTags.length; ++i) {
            var avTag = avTags.get(i);
            avTag.pause(0);
            avTag.src = "";
            avTag.load();
        }
        avTags.remove();
        // Stop existing page load requests
        if ($(document).data().pageLoadRequest != null) {
            Debug.log("Aborting previous request");
            $(document).data().pageLoadRequest.abort();
        }

        $(".more-menu,.dropdownmenu").css("display","none");
        $(".user-bar-icon-hover").removeClass("user-bar-icon-hover");
        NProgress.start();
        onloads = [];
        $("#foot-contents").hide();
        $("#body-contents")[0].innerHTML = "<center><div class='spinner'><div class='spinner-icon'></div></div></center>";
        var hash = "";
        if (url.indexOf("#") > -1) {
            hash = url.substr(url.indexOf("#"));
            url = url.substr(0, url.indexOf("#"));
        }
        var jsonUrl = url + (url.indexOf("?") > -1 ? __svcargs_mid + "__json__" : __svcargs + "__json__");
        if (url.indexOf("__json__") > -1) {
            url = url.replace("__json__", "");
        }
        if (pushState === undefined || pushState === true) {
            history.pushState(null, null, url);
        }
        $.ajaxSetup({
            cache: true
        });
        $(document).data("pageLoadRequest",
            $.ajax({
                url: jsonUrl,
                type: 'POST',
                statusCode: {
                    200: function(resp) {
                        var responseEnd = (new Date()).getTime();
                        var renderStart = responseEnd;
                        var response = resp;
                        try {
                            response = JSON.parse(resp);
                            document.title = response.options.title;
                            $("#body-contents").html(response.contents);
                            if (url != "/error404") {
                                initializePage();
                                Utils.selectContext(JSON.parse(response.options.context));
                                $(".context-menu-signin").attr("href", "/signin/?redr=" + url);
                                $(".context-menu-signout, .link-signout").attr("href", "/signout/?redr=" + url);
                                var renderEnd = (new Date()).getTime();
                                var onloadsStart = renderEnd;
                                for (var i = 0; i < onloads.length; ++i) {
                                    onloads[i]();
                                }
                                if (typeof window.onload === "function") {
                                    window.onload();
                                }
                                var onloadsEnd = (new Date()).getTime();
                                if (Debug.enabled) {
                                    var debugMessage = "Response: " + (responseEnd - responseStart) + " ms, ";
                                    debugMessage += "Render: " + (renderEnd - renderStart) + " ms, ";
                                    debugMessage += "Events: " + (onloadsEnd - onloadsStart) + " ms (" + (onloads.length + (typeof window.onload === "function" ? 1 : 0)) + " events), ";
                                    debugMessage += "Total: " + (onloadsEnd - responseStart) + " ms (" + (onloadsEnd - responseStart) / 1000 + " s)";
                                    Debug.log(debugMessage);
                                }
                            } else {
                                initializePage();
                                Utils.selectContext(null);
                            }
                        } catch (e) {
                            $("#body-contents").html("<span style='color:#ff0000;'>Server error occurred! (Error 500)</span> <a href='javascript:history.go(-1)'>Go back</a>");
                            $("#body-contents").append("<hr/>" + response);
                            initializePage();
                            Utils.selectContext(null);
                            document.title = "Error occurred (500) - Amrayn"
                        }

                    },
                    404: function(resp) {
                        Utils.ajaxPageLoad("/error404", false);
                    }
                }
            }).always(function() {
                $("#foot-contents").show();
                NProgress.done();
                $(document).data('pageLoadRequest', null);
            })
        );
    },
    transitionPageLoad : function(href) {
        window.location = href;
        return;
        $("body")[0].innerHTML = "";
        //$('body').css('background', 'none');
        $('html').css('background', 'url(' + STATIC_IMAGES_BASE + '/load.gif?v=_' + __img_version + ') center 100px no-repeat');
        window.scrollTo(0,0);
        $('body').animate({'opacity': '0', 'top': '-10px'}, 200, function() {
            window.location = href;
        });
    },
    fastPageLoad : function(url, pushState) {
        Utils.transitionPageLoad(url);
    },
    getToken : function(callback) {
        if (typeof callback === "undefined") {
            callback = function() {};
        }
        $.ajax({
            url: '/svc/token' + __svcargs + '_=' + Math.random()
        }).success(function(token) {
            callback(token);
        });
    },
    secondsToTime : function(totalSeconds) {
        hours = Math.floor(totalSeconds / 3600);
        totalSeconds %= 3600;
        minutes = Math.floor(totalSeconds / 60);
        seconds = totalSeconds % 60;
        var t = "";
        if (hours < 10) {
            t += "0" + hours;
        } else {
            t += hours;
        }
        t += ":";
        if (minutes < 10) {
            t += "0" + minutes;
        } else {
            t += minutes;
        }
        if (seconds > 0) {
            t += ":";
            if (seconds < 10) {
                t += "0" + seconds;
            } else {
                t += seconds;
            }
        }
        return t;
    },

    bytesToSize : function(bytes) {
        var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        if (bytes == 0) {
            return '0 Bytes';
        }
        var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
        return Math.round(bytes / Math.pow(1024, i), 2) + ' ' + sizes[i];
    },
    bindHideSection : function(cookieName) {
        $(".hideable-section-head-icon").addClass("fa fa-chevron-down");
        $(".hideable-section-head").click(function() {
            if ($(this).next().is(":visible")) {
                $(this).next().slideUp();
                $(this).find(".hideable-section-head-icon").removeClass("fa fa-chevron-down").addClass("fa fa-chevron-right");
                var existing = cook(cookieName, "").split(",");
                if (existing.indexOf($(this).attr("id")) === -1) {
                    existing.push($(this).attr("id"));
                    saveCookie(cookieName, existing.join(","));
                }
            } else {
                $(this).next().slideDown();
                $(this).find(".hideable-section-head-icon").removeClass("fa fa-chevron-right").addClass("fa fa-chevron-down");
                var existing = cook(cookieName, "").split(",");
                existing.splice(existing.indexOf($(this).attr("id")), 1);
                saveCookie(cookieName, existing.join(","));
            }
        });
        var hiddenSections = cook(cookieName, "").split(",");
        for (var i = 0; i < hiddenSections.length; ++i) {
            $("#" + hiddenSections[i]).next().hide();
            $("#" + hiddenSections[i]).find(".hideable-section-head-icon").removeClass("fa fa-chevron-down").addClass("fa fa-chevron-right");
        }
    }
};
var Bookmark = {
    checkOnPage : function() {
        Bookmark.checkNew($(".not-bookmarked:not(.bookmark-checked)"));
    },
    checkNew : function(refs) {
        if (refs.length == 0 || !isLoggedIn) {
            return;
        }
        const ref = $(refs[0]);
        var onlykeyid = Number(ref.attr('keyid'));
        ref.addClass('small-spinner').addClass("bookmark-checked");
        fetch('/api/v1/token', {
          method: 'post',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            expiry: 20,
            scope: '/api/v1/bookmark/list',
          }),
        }).then(r => r.json()).then((tokenData) => {
          fetch('/api/v1/bookmark/list', {
            method: 'post',
            headers: {
              'Accept': 'application/json',
              'Content-Type': 'application/json',
              Authorization: 'Bearer ' + tokenData.token,
            },
            body: JSON.stringify({
              types: [1],
            }),
          }).then(r => r.json()).then((result) => {
            const hadith = result['1'];
            if (hadith.indexOf(onlykeyid) > -1) {
              ref.removeClass("not-bookmarked").addClass("bookmarked");
            }

            ref.removeClass('small-spinner');
          });
        });
    },

    toggleNew : function(ref) {
        var onlykeyid = Number(ref.attr('keyid'));
        var action = ref.hasClass('not-bookmarked') ? 'add' : 'remove';
        fetch('/api/v1/token', {
          method: 'post',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            expiry: 20,
            scope: '/api/v1/bookmark/' + action,
          }),
        }).then(r => r.json()).then((tokenData) => {
          fetch('/api/v1/bookmark/' + action, {
            method: 'post',
            headers: {
              'Accept': 'application/json',
              'Content-Type': 'application/json',
              Authorization: 'Bearer ' + tokenData.token,
            },
            body: JSON.stringify({
              id: onlykeyid,
              type: 1,
            }),
          }).then(r => r.json()).then((result) => {
            if (action == 'add') {
                ref.attr('title', 'Unbookmark');
                ref.removeClass("not-bookmarked").addClass("bookmarked");
            } else {
                ref.attr('title', 'Bookmark');
                ref.removeClass("bookmarked").addClass("not-bookmarked");

            }
          });
        });
    },
    check : function(refs) {
        if (refs.length == 0 || !isLoggedIn) {
            return;
        }
        var keys = [];
        for (var i = 0; i < refs.length; ++i) {
            $(refs[i]).addClass('small-spinner').addClass("bookmark-checked");
            keys.push($(refs[i]).attr('key'));
        }
        $.ajax({
            url:'/svc/bookmark' + __svcargs + '_=' + Math.random(),
            type: 'POST',
            timeout: 10000,
            data: {
                'action' : 'chk',
                'key' : keys.join(",")
            },
            statusCode: {
                200: function(data) {
                    Debug.log(data);
                    Bookmark.updateIcons(data);
                },
                400: function(data) {
                    Utils.notifyUser({text: data.responseText, type: 'error', timeout: 3000 });
                }
            }
        }).complete(function(data) {
            for (var i = 0; i < refs.length; ++i) {
                $(refs[i]).removeClass('small-spinner');
            }
        });
    },
    updateIcons : function(data) {
        var result = $.isArray(data) ? data : JSON.parse(data);
        for (var i = 0; i < result.length; ++i) {
            if (result[i].v == "true") {
                $(".not-bookmarked[key='" + result[i].k + "']").removeClass("not-bookmarked").addClass("bookmarked");
            } else {
                $(".bookmarked[key='" + result[i].k + "']").removeClass("bookmarked").addClass("not-bookmarked");
            }
        }
    },
    toggle : function(ref, callback, showProgress, showError, analytics) {
        if (showProgress === undefined) {
            showProgress = true;
        }
        if (showError === undefined) {
            showError = true;
        }
        if (analytics === undefined) {
            analytics = true;
        }
        if (showProgress) {
            NProgress.start();
        }
        ref.addClass('small-spinner');

        var key = ref.attr('key');
        var btype = ref.attr('btype');
        var action = ref.hasClass('not-bookmarked') ? 'add' : 'rem';
        var originalClass = action == 'add' ? 'not-bookmarked' : 'bookmarked';
        var successClass = action == 'add' ? 'bookmarked' : 'not-bookmarked';
        Utils.getToken(function(token) {
            $.ajax({
                url:'/svc/bookmark' + __svcargs + '_=' + Math.random(),
                type: 'POST',
                timeout: 10000,
                data: {
                    'token' : token,
                    'action' : action,
                    'key' : key
                },
                statusCode: {
                    200: function() {
                        ref.removeClass(originalClass).addClass(successClass);
                        if (action == 'add') {
                            ref.attr('title', 'Unbookmark');
                            if (analytics) {
                              Analytics.send('Bookmark', 'Add_Bookmark', btype);
                            }

                        } else {
                            ref.attr('title', 'Bookmark');
                            if (analytics) {
                              Analytics.send('Bookmark', 'Remove_Bookmark', btype);
                            }
                        }

                        if (typeof callback === "function") {
                            callback();
                        }
                    },
                    400: function(data) {
                        if (showError) {
                            Utils.notifyUser({text: data.responseText, type: 'error', timeout: 3000});
                        }
                        if (typeof callback === "function") {
                            callback();
                        }
                    }
                }
            }).always(function() {
                ref.removeClass('small-spinner');
                NProgress.done();
            });
        });
        return false;
    }
};
sortSelectByName = function(id, asInt) {
    var options = $(id + ' option');
    var selectedValBefore = $(id + ' option:selected').val();
    var arr = options.map(function(_, o) {
        if (asInt) {
            return { t: parseInt($(o).text()), v: parseInt($(o).val()), sel: $(o).attr('selected') };
        } else {
            return { t: $(o).text(), v: $(o).val(), sel: $(o).attr('selected') };
        }
    }).get();
    arr.sort(function(o1, o2) {
        return o1.t > o2.t ? 1 : o1.t < o2.t ? -1 : 0;
    });
    options.each(function(i, o) {
        $(o).val(arr[i].v);
        $(o).text(arr[i].t);
        if (arr[i].sel != undefined) {
            $(o).attr('selected', 'true');
        }
    });
    if (selectedValBefore != $(id + ' option:selected').val()) {
        $(id).val(selectedValBefore);
    }
}

endsWith = function(str, suffix) {
    return str.indexOf(suffix, str.length - suffix.length) !== -1;
}

paddedNumber = function(n, width, z) {
    z = z || '0';
    n = n + '';
    return n.length >= width ? n : new Array(width - n.length + 1).join(z) + n;
}

parseBool = function(str) {
    return $.parseJSON(str);
}

/**
 * Parse value with type. so "1" will return 1
 * if no match found returns str itself
 */
parseValue = function(str) {
    if (typeof str != "string") {
        return str;
    }
    if (str.match(/^\d+$/)) {
        return parseInt(str);
    }
    if (str.match(/^true$/i)) {
        return true;
    }
    if (str.match(/^false$/i)) {
        return false;
    }
    return str;
}

cook = function(cookieName, defaultValue) {
    return $.cookie(cookieName) == undefined ? defaultValue : $.cookie(cookieName);
}

saveCookie = function(cookieName, value, crossDomain) {
    if (typeof crossDomain === "undefined") {
        crossDomain = false;
    }
    var options = { expires: 360, path : '/', domain : crossDomain ? '.amrayn.com' : 'amrayn.com'};
    $.cookie(cookieName, value, options);
}

removeCookie = function(cookieName) {
    $.removeCookie(cookieName, { path: '/', domain: '.amrayn.com' });
    $.removeCookie(cookieName, { path: '/', domain: 'amrayn.com' });
}

shortenUrl = function(longUrl, callback) {
    var result = longUrl;
    $.ajax({
        url: "/shorten?u=" + encodeURIComponent(longUrl),
        async: true,
        timeout: 7000
    }).done(function(data) {
        result = data;
        callback(result);
    }).fail(function(data) {
        callback("http://amrayn.com/" + result);
    });
}
selectInit = function(selector, options) {
}
multiSelectInit = function(selector, options) {
    $(selector).chosen(options);
    $("#cbo_translations_chosen").css("width", "40%");
    $(selector).trigger("chosen:updated");
}
spinInit = function(selector, options) {
    $(selector).spinner(options);
}
intVal = function(selector, value) {
    if (value == undefined) {
        return isNaN(parseInt($(selector).val())) ? null : parseInt($(selector).val());
    } else {
        $(selector).val(value);
    }
}

spinOption = function(selector, optionName, optionValue) {
    $(selector).spinner("option", optionName, optionValue);
}

var spinStopEvent = "spinstop";
var spinChangeEvent = "spinchange";

sendTimeTakenEvent = function(start, eventName) {
    if (start != null) {
        var end = new Date();
        var ms = end - start;
        var sec = ms / 1000;
        Analytics.time(eventName, sec);
    }
}
flash = function(jqObj, flashSpeed) {
    jqObj.fadeIn(flashSpeed).fadeOut(flashSpeed).fadeIn(flashSpeed).fadeOut(flashSpeed).fadeIn(flashSpeed).fadeOut(flashSpeed).fadeIn(flashSpeed);
}
// http://stackoverflow.com/questions/11887934/check-if-daylight-saving-time
Date.prototype.stdTimezoneOffset = function() {
    var jan = new Date(this.getFullYear(), 0, 1);
    var jul = new Date(this.getFullYear(), 6, 1);
    return Math.max(jan.getTimezoneOffset(), jul.getTimezoneOffset());
}
Date.prototype.dst = function() {
    return this.getTimezoneOffset() < this.stdTimezoneOffset();
}
getQueryParams = function(qs) {
    if (qs === undefined) {
        qs = document.location.search;
    }
    qs = qs.split("+").join(" ");

    var params = {}, tokens, re = /[?&]?([^=]+)=([^&]*)/g;

    while (tokens = re.exec(qs)) {
        params[decodeURIComponent(tokens[1])] = decodeURIComponent(tokens[2]);
    }

    return params;
}
loadDialog = function(selector, contentsUrl, callback) {
    if (typeof callback !== 'function') {
        callback = function() {};
    }
    selector.load('/dialogcontents/' + contentsUrl, callback);
}
openDialog = function(selector, contentsUrl, beforeOpenCallback, afterOpenCallback) {
    if (selector.text().trim().length > 1) {
        if (typeof beforeOpenCallback === 'function') {
            beforeOpenCallback();
        }
        selector.dialog({
            maxHeight: $(window).height() - 50,
            height: 'auto',
            width: '80%',
            modal: true,
            dialogClass: 'fixed-dialog',
            open: function(event, ui) {
                $('.ui-widget-overlay').bind('click', function() {
                    selector.dialog('close');
                });
            }
        });
        if (typeof afterOpenCallback === 'function') {
            afterOpenCallback();
        }
        return false;
    } else {
        loadDialog(selector, contentsUrl, function() {
            openDialog(selector, contentsUrl, beforeOpenCallback, afterOpenCallback);
        });
    }
}

replaceNL = function(str, replacement) {
    return str.replace(/\r\n|\r|\n/g, replacement);
}

br2nl = function(str) {
    return str.replace(/<br\s*\/?>/mg,"\n");
}

nl2br = function(str) {
    return replaceNL(str, "<br>");
}

countShares = function() {
    // Facebook
    jQuery.ajax({
        url:"http://graph.facebook.com/?id=" + window.location.href,
        success: function(data) {
            if (data.share) {
                var value = data.share.share_count;
                jQuery("#fb-share-button").find(".count-val").remove();
                jQuery("#fb-share-button").append("<sup class='count-val'>" + value + "</sup>");
            }
        }
    })
}
