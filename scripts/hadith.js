	var loadStartTime = null;
	var REF_LINK_NAMES = ["bukhari", "muslim", "abudawood", "malik", "qudsi", "tirmidhi", "ibnmajah", "nawawi", "nasai", "riyadussaliheen", "shamail"];
	var analyticsRef = null;
	var arabicHadithEventSent = false;
	hasBookNames = function(collectionID) {
		return collectionID == "1" || collectionID == "2" || collectionID == "3" || collectionID == "4" || collectionID == "7" || collectionID == "9" || collectionID == "10" || collectionID == "6" || collectionID == "11";
	}
	hasBooks = function(collectionID) {
		return collectionID != "5" && collectionID != "8";
	}
	hasVolumes = function(collectionID) {
		return collectionID == "1" || collectionID == "2" || collectionID == "3" || collectionID == "6" || collectionID == "7" || collectionID == "9";
	}

	var GradeFlags = {
		"1" : {"id": 1, "class_" : "hadith-tag-sahih", "text": "Sahih"},
		"2" : {"id": 2, "class_" : "hadith-tag-hasan", "text": "Hasan"},
		"3" : {"id": 4, "class_" : "hadith-tag-daeef", "text": "Da`eef"},
		"4" : {"id": 8, "class_" : "hadith-tag-moudu", "text": "Moudu`"},
		"5" : {"id": 16, "class_" : "hadith-tag-hasansahih", "text": "Hasan Sahih"},
		"6" : {"id": 32, "class_" : "hadith-tag-munkar", "text": "Munkar"},
		"7" : {"id": 64, "class_" : "hadith-tag-shadhdh", "text": "Shadhdh"},
		"8" : {"id": 128, "class_" : "hadith-tag-mauquf", "text": "Mauquf"},
		"9" : {"id": 256, "class_" : "hadith-tag-maqtu", "text": "Maqtu`"},
		"10" : {"id": 512, "class_" : "hadith-tag-sahihinchain", "text": "Sahih in Chain"},
		"11" : {"id": 1024, "class_" : "hadith-tag-daeefjiddan", "text": "Da`eef Jiddan"},
		"12" : {"id": 2048, "class_" : "hadith-tag-hasaninchain", "text": "Hasan in Chain"},
		"13" : {"id": 4096, "class_" : "hadith-tag-sahihlighirih", "text": "Sahih li-ghairih"},
		"14" : {"id": 8192, "class_" : "hadith-tag-marfu", "text": "Marfu`"},
		"15" : {"id": 16384, "class_" : "hadith-tag-mutawatir", "text": "Mutawatir"},
		"16" : {"id": 32768, "class_" : "hadith-tag-mursal", "text": "Mursal"},
		"17" : {"id": 65536, "class_" : "hadith-tag-lighairih", "text": "Hasan li-ghairih"},
		"18" : {"id": 131072, "class_" : "hadith-tag-nochainfoundalalbani", "text": "No chain found (Al-Albani)"},
		"19" : {"id": 262144, "class_" : "hadith-tag-chainisdaeefalalbani", "text": "Chain is da`eef (Al-Albani)"},
		"20" : {"id": 524288, "class_" : "hadith-tag-hasangharib", "text": "Hasan Gharib"},
		"21" : {"id": 1048576, "class_" : "hadith-tag-qudsi", "text": "Qudsi"},
		"22" : {"id": 2097152, "class_" : "hadith-tag-sahihmouquf", "text": "Sahih Mouquf"},
		"23" : {"id": 4194304, "class_" : "hadith-tag-unknown", "text": "No Data"},
		"24" : {"id": 8388608, "class_" : "hadith-tag-gharib", "text": "Gharib"},
		"25" : {"id": 16777216, "class_" : "hadith-tag-munqati", "text": "Munqati`"},
		"26" : {"id": 33554432, "class_" : "hadith-tag-sahihbyalbani", "text": "Sahih (Al-Albani)"},
		length : 26
	};
	buildGradeLabelsHTML = function(gradeFlags) {
		if (gradeFlags == null) {
			gradeFlags = "4194304"; // Unknown
		}
		var finalHTML = "<br/><div class='hadith-section-label'>Classification</div>";
		var grade = parseInt(gradeFlags);
		for (var i = 1; i <= GradeFlags.length; ++i) {
			if (grade & GradeFlags[i].id) {
				finalHTML += "<span class='hadith-tag " + GradeFlags[i].class_ + "'>" + GradeFlags[i].text + "</span>";
			}
		}
		return finalHTML;

	}
	buildReferenceHTML = function(secondaryRef, volume) {
		var collection = $("#hadith-collections option:selected").val();
		var book = $("#books option:selected").val();
		var bookName = $("#books option:selected").text();
		var hadith = $("#hadiths option:selected").text();
		var finalHTML = "<br/><div class='hadith-section-label'>References</div>";
		var style1 = "&nbsp;&nbsp;&nbsp;&nbsp;<span class='hadith-ref'>";
		var style1Name = "";
		switch (collection) {
			case "1":style1Name += "al-Bukhari";break;
			case "2":style1Name += "Muslim";break;
			case "3":style1Name += "Abu Dawood";break;
			case "4":style1Name += "Malik";break;
			case "5":style1Name += "Hadith Qudsi";break;
			case "6":style1Name += "al-Tirmidhi";break;
			case "7":style1Name += "Ibn Majah";break;
			case "8":style1Name += "al-Nawawi 40";break;
			case "9":style1Name += "al-Nasaa’i";break;
			case "10":style1Name += "Riyaad-us-saliheen";break;
			case "11":style1Name += "Shamail at-Tirmidhi";break;
		}
		var styleNameFull = "";
		switch (collection) {
			case "1":styleNameFull += "Sahih al-Bukhari";break;
			case "2":styleNameFull += "Sahih Muslim";break;
			case "3":styleNameFull += "Sunan Abu Dawood";break;
			case "4":styleNameFull += "Muwatta Malik";break;
			case "5":styleNameFull += "Hadith Qudsi";break;
			case "6":styleNameFull += "Jami` at-Tirmidhi";break;
			case "7":styleNameFull += "Sunan Ibn Majah";break;
			case "8":styleNameFull += "al-Nawawi 40";break;
			case "9":styleNameFull += "Sunan an-Nasaa’i";break;
			case "10":styleNameFull += "Riyaad-us-saliheen";break;
			case "11":styleNameFull += "Shamail at-Tirmidhi";break;
		}
		style1+=style1Name;
		if (hasBooks(collection)) {
			style1 += " " + book;
		}
		style1 += "/" + hadith;
		style1 += "</span>";
		var style2 = "&nbsp;&nbsp;&nbsp;&nbsp;<span class='hadith-ref'>";
		switch (collection) {
			case "1":style2 += "bukhari";break;
			case "2":style2 += "muslim";break;
			case "3":style2 += "abudawood";break;
			case "4":style2 += "malik";break;
			case "5":style2 += "qudsi";break;
			case "6":style2 += "tirmidhi";break;
			case "7":style2 += "ibnmajah";break;
			case "8":style2 += "nawawi";break;
			case "9":style2 += "nasai";break;
			case "10":style2 += "riyaad-us-saliheen";break;
			case "11":style2 += "shamail";break;
		}
		if (hasBooks(collection)) {
			style2 += " " + book;
		}
		style2 += "/" + hadith;
		style2 += "</span>";
		var style3 = "&nbsp;&nbsp;&nbsp;&nbsp;<span class='hadith-ref'>";
		switch (collection) {
			case "1":style3 += "al-Bukhari";break;
			case "2":style3 += "Muslim";break;
			case "3":style3 += "Abu Dawood";break;
			case "4":style3 += "Malik";break;
			case "5":style3 += "Hadith Qudsi";break;
			case "6":style3 += "al-Tirmidhi";break;
			case "7":style3 += "Ibn Majah";break;
			case "8":style3 += "Nawawi 40";break;
			case "9":style3 += "al-Nasaa’i";break;
			case "10":style3 += "Riyaad-us-saliheen";break;
			case "11":style3 += "Shamail at-Tirmidhi";break;
		}
		if (hasBooks(collection) && hasBookNames(collection)) {
			style3 += " Book of " + bookName.substr(3);
			style3 += " #" + hadith;
		} else {
			if (hasBooks(collection)) {
				style3 += " " + book;
			}
			style3 += "/" + hadith;
		}
		style3 += "</span>";
		var style4 = "&nbsp;&nbsp;&nbsp;&nbsp;<span class='hadith-ref'>";
		style4 += styleNameFull;
		if (hasVolumes(collection)) {
			style4 += " Vol. " + volume + ", ";
		}
		if (hasBooks(collection)) {
			style4 += " Book " + book + ", ";
		}
		style4 += " Hadith " + hadith;
		style4 += "</span>";
		var style5 = "";
		if (secondaryRef != null) {
			style5 = "&nbsp;&nbsp;&nbsp;&nbsp;<span class='hadith-ref'>";
			style5 += style1Name;
			style5 += " " + secondaryRef;
		}
		finalHTML += style3;
		if (style5 != "") {
			finalHTML += "<br/>" + style5;
		}
		if (hasVolumes(collection) && typeof volume != "undefined" && volume != 0) {
			finalHTML += "<br/>" + style4;
		}
		// Full reference
		var style6 = "&nbsp;&nbsp;&nbsp;&nbsp;<span class='hadith-ref'>";
		style6 += styleNameFull;
		if (hasVolumes(collection)) {
			style6 += " Vol. " + volume + ", ";
		}
		if (hasBooks(collection) && hasBookNames(collection)) {
			style6 += " Book of " + bookName.substr(3) + ", ";
		} else {
			if (hasBooks(collection)) {
				style6 += " " + book;
			}
		}
		style6 += " Hadith " + hadith;
		style6 += "</span>";
		finalHTML += "<br/>" + style6;
		return finalHTML;
	}
	var Hadith = {
		launchApp : function(collection, book, hadith) {
		    window.open("hadith://collection/book/hadith", "_self");
		},
		appInstalled : function() {
			return Utils.cook("happ", null) !== null;
		}
	}
	var HadithEditor = {
		hadithForReview: null,
		canEdit : false,
		canMerge : false,
		editing : false,
		original : null,
		modified : null,
		successMessageTimeout : 5000,
		errorMessageTimeout : 10000,
		saveHadith : function(ref, callbackOnMerge) {
			if (HadithEditor.canMerge && !confirm("Are you sure you wish MERGE this hadith as you have merge permissions? (YOU CANNOT UNDO THIS!)")) {
				return;
			}
			NProgress.start();
			ref.addClass('small-spinner');
      /*fetch('/svc/edithadith' + __svcargs, {
        body: JSON.stringify({
          data: HadithEditor.modified
        }),
        headers:{
          'Content-Type': 'application/json',
        },
        method: 'POST',
      }).then(f => f.json()).then(resp => {
        if (resp.error) {
					Utils.notifyMessage({text: resp.message, type: 'error', timeout: HadithEditor.errorMessageTimeout });
				} else {
					if (typeof resp.new_last_updated !== "undefined") {
						HadithEditor.modified.lastUpdated = resp.new_last_updated;
					}
					if (typeof resp.new_hadith_version !== "undefined") {
						HADITH_VERSION = resp.new_hadith_version;
					}
					if (HadithEditor.canMerge && typeof callbackOnMerge === "function") {
						callbackOnMerge();
					} else {
						$(".unsaved-changes-icon").hide();
					}
					Utils.notifyMessage({ text: resp.message, type: 'success', timeout: HadithEditor.successMessageTimeout });
				}
				ref.removeClass('small-spinner');
				NProgress.done();
      });*/

			$.ajax({
				url : '/svc/edithadith' + __svcargs,
				data : {
					data : HadithEditor.modified
				},
				type: 'POST'
			}).always(function(resp) {
				if (resp.error) {
					Utils.notifyMessage({text: resp.message, type: 'error', timeout: HadithEditor.errorMessageTimeout });
				} else {
					if (typeof resp.new_last_updated !== "undefined") {
						HadithEditor.modified.lastUpdated = resp.new_last_updated;
					}
					if (typeof resp.new_hadith_version !== "undefined") {
						HADITH_VERSION = resp.new_hadith_version;
					}
					if (HadithEditor.canMerge && typeof callbackOnMerge === "function") {
						callbackOnMerge();
					} else {
						$(".unsaved-changes-icon").hide();
					}
					Utils.notifyMessage({ text: resp.message, type: 'success', timeout: HadithEditor.successMessageTimeout });
				}
				ref.removeClass('small-spinner');
				NProgress.done();
			});
		},
		cancelProposal : function(ref, callback) {
			if (confirm("Are you sure you wish to CANCEL the proposal? (YOU CANNOT UNDO THIS!)")) {
				if (HadithEditor.hadithForReview == null) {
					if (typeof callback === "function") {
						callback();
					}
				} else {
					NProgress.start();
					ref.addClass('small-spinner');
					$.ajax({
						url : '/svc/edithadith' + __svcargs,
						data : {
							"fastIdentifier" : HadithEditor.hadithForReview.fastIdentifier,
							"cancel" : true
						},
						type: 'POST'
					}).always(function(resp) {
						if (resp.error) {
							Utils.notifyMessage({text: resp.message, type: 'error', timeout: HadithEditor.errorMessageTimeout });
						} else {
							if (typeof callback === "function") {
								callback();
							}
							Utils.notifyMessage({ text: resp.message, type: 'success', timeout: HadithEditor.successMessageTimeout });
						}
						ref.removeClass('small-spinner');
						NProgress.done();
					});
				}
			}
			return false;
		},
		proposalInfo : function() {
			if (HadithEditor.hadithForReview !== null) {
				alert("Reviews: +" + HadithEditor.hadithForReview.positiveReviews + " (Positive reviews) -" + HadithEditor.hadithForReview.negativeReviews + " (Negative reviews)");
			} else {
				alert("Please submit for review and check later for reviews.");
			}
		},
		renderModified : function() {
			if ($("#tab3>.new-text").length == 0) {
				$("#tab3").html("<div class='new-text'>" + nl2br(HadithEditor.modified.text) + "</div>");
			} else {
				$("#tab3>.new-text").html(nl2br(HadithEditor.modified.text));
			}
			$(".hadith-text").show();
			$(".hadith-edit-ctl").remove();
			HadithEditor.editing = false;
			initializeTabs();
			$("#tabs > li:nth(2) > a").click();
		},
		editHadith : function() {
			if (HadithEditor.editing) {
				return;
			}
			$("#tabs > li:nth(0) > a").click();
			HadithEditor.editing = true;
			if (HadithEditor.modified == null) {
				// Deep copy original so we can modify and have both copies available
				HadithEditor.modified = jQuery.extend(true, {}, HadithEditor.original);
			}
			var area = $(".hadith-text");
			if ($("#tab3>.new-text").text().length > 0) {
				area = $("#tab3>.new-text");
			}
			var editArea = $("<textarea></textarea>");
			editArea.attr("class", "hadith-edit-ctl hadith-text-edit");
			editArea.css({
				'width': $(".hadith-text").width()-10,
				'height': $(".hadith-text").height(),
	  			'font-size': '1em',
	  			'line-height': '1.3em',
	  			'font-family': 'Gentium Basic'
			});
			editArea.val(br2nl(replaceNL(area.html(), "")));
			$(".hadith-container").append(editArea);
			var editOk = $("<button></button>");
			editOk.attr("class", "hadith-edit-ctl hadith-text-edit-ok taskbtn");
			editOk.html("OK");
			editOk.css("font-size", "0.8em");
			editOk.click(function() {
				$(".unsaved-changes-icon").show();
				HadithEditor.modified.text = $(".hadith-text-edit").val();
				HadithEditor.modified.textArabic = $(".hadith-text-arabic").text(); // inline editing cannot change arabic text and this is important because br2nl (server side) causes <br/>\n to return instead of <br/>
				HadithEditor.renderModified();
			});
			var editCancel = $("<button></button>");
			editCancel.attr("class", "hadith-edit-ctl hadith-text-edit-cancel taskbtn");
			editCancel.html("Cancel");
			editCancel.css("font-size", "0.8em");
			editCancel.click(function() {
				$(".hadith-text").show();
				$(".hadith-edit-ctl").remove();
				HadithEditor.editing = false;
			});
			$(".hadith-container").append("<br class='hadith-edit-ctl '/>");
			$(".hadith-container").append(editOk);
			$(".hadith-container").append("<span class='hadith-edit-ctl'>&nbsp;&nbsp;&nbsp;<span>");
			$(".hadith-container").append(editCancel);
			// Make cross ref editor
			var linksHtml = "";
			if (HadithEditor.modified.links == null) {
				// We need label first
				linksHtml = "<div class='hadith-text-edit-ctl'><br/><br/><div style='display:inline' class='hadith-section-label hadith-cross-ref'>Cross References</div></div>";
				//$(".hadith-cross-ref").hide();
			}
			$(".hadith-text").hide();
		}
	};
	buildCrossReferenceHTML = function(links) {
		if (!links || links.length === 0) {
			return "";
		}
		var finalHTML = "<br/><div class='hadith-section-label hadith-cross-ref'>Cross References</div>";
		for (var i = 0; i < links.length; ++i) {
			var link = links[i];
			finalHTML += "&nbsp;&nbsp;&nbsp;&nbsp;<a href='" + link.l + "' class='hadith-ref hadith-cross-ref local'>" + link.t.replace("Quraan", "Qur'an").trim() + "</a><br/>";
		}
		return finalHTML;
	}

	$(document).on("click", ".hadith-tag", function() {
		openClassificationDialog($(this));
	});
	openClassificationDialog = function(sender) {
		openDialog($("#hadith-classifications-dialog"), "hadith/hadith-classifications", function() {}, function() {
			if (typeof hideMobileMenu === "function") {
				hideMobileMenu();
			}
			var tagElem = $('#hadith-classifications > a[name=\'' + sender.text().toLowerCase().replace(/ /g,'-').replace("`","") + '-tag\']');
			var tagElemPosition = tagElem.position();
			if (tagElemPosition != undefined) {
				$('#hadith-classifications-dialog').scrollTop(tagElemPosition.top < 20 ? 0 : tagElemPosition.top - 20);
				tagElem.animate({backgroundColor: sender.css('background-color'), color: sender.css('color')}, 1500).animate({backgroundColor: 'transparent', color: '#000'}, 'slow');
			}
		});
	}
	buildServiceUrl = function(params, newParams) {
    if (window.localStorage.getItem('newapi') === 'true' && newParams /* new params has hadith */) {
      var collectionVal = $("#hadith-collections option:selected").val();
      var collectionName = REF_LINK_NAMES[Number(collectionVal) - 1];
      return "/api/v1/hadith/fetch/" + collectionName + "?backwardsCompatible=true&" + newParams.join("&") + "&hv=" + HADITH_VERSION;
    }
		return "/svc/hadith?" + params.join("&") + "&hv=" + HADITH_VERSION;
	}
        buildServiceUrlForNote = function(params) {
                return "/svc/hadinote" + __svcargs_no_ver + params.join("&") + "&hv=" + HADITH_VERSION;
        }
        buildServiceUrlForReview = function(params) {
                return "/svc/hadireview" + __svcargs_no_ver + params.join("&") + "&hv=" + HADITH_VERSION;
        }
	buildReviewHTML = function(callback) {
		var params = [];
		params.push("c=" + $("#hadith-collections option:selected").val());
		params.push("b=" + $("#books option:selected").val());
		params.push("h=" + $("#hadiths option:selected").val());
		$.getJSON(buildServiceUrlForReview(params) + "&_=" + Math.random(), function(data) {
			if (data != null) {
				HadithEditor.hadithForReview = data;
				if (HadithEditor.original.text != data.newText) {
					$("#tab3").html("<div class='hadith-section-label'>English:</div><div class='new-text'>" + data.newText + "</div>");
				}
				if (HadithEditor.original.textArabic != data.newArabicText) {
					$("#tab3").append("<div class='hadith-section-label'>Arabic:</div><div class='new-text hadith-text-arabic arabic'>" + data.newArabicText + "</div>");
				}
				if (HadithEditor.original.gradeFlag != data.newGradeFlag) {
					$("#tab3").append("<div class='new-grade'>" + buildGradeLabelsHTML(data.newGradeFlag) + "</div>");
				}
				if (JSON.stringify(HadithEditor.original.links) != JSON.stringify(data.newLinks)) {
					if (data.newLinks != null) {
						$("#tab3").append("<div class='new-links'>" + buildCrossReferenceHTML(data.newLinks) + "</div>");
					} else {
						$("#tab3").append("<div class='new-links'><br>Cross References<br>&nbsp;&nbsp;&nbsp;<span style='color:red'>ALL LINKS REMOVED</span></div>");
					}
				}
				if (HadithEditor.original.references_data != data.newReferences) {
					$("#tab3").append("<div class='hadith-section-label'>References</div><div class='new-references'>" + data.newReferences + "</div>");
				}
				if (HadithEditor.original.tags != data.newTags) {
					$("#tab3").append("<div class='hadith-section-label'>Tags:</div><div class='new-tags'>" + data.newTags + "</div>");
				}
				initializeTabs();
			}
		});
	}
	buildNoteHTML = function(callback) {
    return; // no longer supported
		var params = [];
		params.push("c=" + $("#hadith-collections option:selected").val());
		params.push("b=" + $("#books option:selected").val());
		params.push("h=" + $("#hadiths option:selected").val());
		$.getJSON(buildServiceUrlForNote(params) + "&_=" + Math.random(), function(data) {
			if (data != null && data.text.length > 0) {
				var html = "<br><div class='hadith-section-label'>Note<span class='delete-hadith-note fa fa-close' key='" + data.fastIdentifier + "' keyid='" + data.keyid + "'></span></div>";
				html += "<div style='padding-left:0.5em;padding-top:0.5em;'>" + data.text + "</div><br><span style='color:#999;font-size:0.8em;'><i style='padding-left:1em;' class='fa fa-user-secret'> Only you can see this</span>";
				callback(html);
			} else {
				callback("");

			}
		});
	}
	bindDeleteHadithNote = function() {
    return; // no longer supported
		$(".delete-hadith-note").click(function() {
			if (isLoggedIn && confirm("Are you sure you wish delete this hadith note? (YOU CANNOT UNDO THIS!)")) {
				var baseUrl = "/svc/edit-hadith-note/";
				var fullUrl = baseUrl + $(this).attr("key") + __svcargs_no_ver + "&remove&hv=" + HADITH_VERSION;
				$.getJSON(fullUrl + "&_=" + Math.random(), function(resp) {
					if (resp.error == true) {
						Utils.notifyMessage({ text: resp.message, type: 'error', timeout: 10000 });
					} else {
						Utils.notifyMessage({ text: resp.message, type: 'success', timeout: 5000 });
						$(".hadith-note").remove();
					}
				});
			}
		});
	}
	collectionChanged = function(callback) {
		loadStartTime = new Date();
		loading();
		var collection = $("#hadith-collections option:selected").val();
		if (callback == undefined) {
			callback = function() {
						  bookChanged(function() {
							  hadithChanged(null, true)
						  })
					  };
		}
		$("#span-books").hide();
		$("#span-hadiths").hide();
		var params = [];
		params.push("c=" + collection);
		if (collection === undefined) {
			return;
		}
		//Utils.updateBreadcrumb("Collection", "/" + REF_LINK_NAMES[collection - 1], $("#hadith-collections option:selected").attr("ref-text"));
		$.getJSON(buildServiceUrl(params), function(data) {
			if (hasBookNames(collection)) {
				var params = [];
				params.push("c=" + collection);
				params.push("bn");
				$.getJSON(buildServiceUrl(params), function(data) {
					var currentVolume = null;
					$('#books').find('optgroup').remove();
					$('#books').find('option').remove();

					$.each(data, function(key, value) {
						var currentGroup = $('#books');
						if (value.volume !== null && currentVolume != value.volume) {
							currentGroup = $('<optgroup>');
							currentGroup.attr('label', "Volume " + value.volume);
							currentVolume = value.volume
						} else if (value.volume !== null && currentVolume == value.volume) {
							currentGroup = $('#books').find("optgroup:last-child")
						}
						if (currentVolume != null) {
							currentGroup.append($("<option></option>")
								.attr("value",value.book)
								.text(value.book + ". " + value.bookName));
                        	$('#books').append(currentGroup);
						} else {
							$('#books').append($("<option></option>")
								.attr("value",value.book)
								.text(value.book + ". " + value.bookName));
						}
					});

					if (hasBooks(collection)) {
						$('#span-books').show();
					}
					loadingComplete();
					callback();
				});
			} else {
				$('#books').find('option').remove();
				$.each(data, function(key, value) {
					$('#books')
						.append($("<option></option>")
						.attr("value",value.book)
						.text(value.book));
				});
				if (hasBooks(collection)) { // Hadith Qudsi and Nawawi
					$('#span-books').show();
				}
				loadingComplete();
				callback();
			}
		});
	}
	bookChanged = function(callback) {
		loadStartTime = new Date();
		loading();
		var collection = $("#hadith-collections option:selected").val();
		if (callback == undefined) {
			callback = function() {
						  hadithChanged(null, true)
					  };
		}
		var params = [];
		params.push("c=" + collection);
		params.push("b=" + $("#books option:selected").val());
		$.getJSON(buildServiceUrl(params), function(data) {
			$('#hadiths').find('option').remove();
			$.each(data, function(key, value) {
				$('#hadiths')
					.append($("<option></option>")
					.attr("value", value.hadith)
					.text(value.hadith));
			});
			$('#span-hadiths').show();
			loadingComplete();
			callback();
		});
	}
	hadithChanged = function(callback, autoLoading) {
		analyticsRef = null;
		arabicHadithEventSent = false;
		HadithEditor.original = null;
		HadithEditor.modified = null;
		HadithEditor.canEdit = User.Permissions.canEditHadith;
		HadithEditor.canMerge = User.Permissions.canMergeHadith;
		HadithEditor.hadithForReview = null;
		var reloadCount = $(document).data().hadithReloadCount;
		if (typeof reloadCount == "undefined") {
			reloadCount = 0;
		}
		if (reloadCount >= 10) {
			// We are probably in different context before loading was finished. So we abort
			Debug.log("Aborting hadith load request as we have exhaused the retries");
			$(document).data("hadithReloadCount", 0);
			return;
		}
		loadStartTime = new Date();
		loading();
		var collection = $("#hadith-collections option:selected").val();
    var params = [];
    var newParams = [];
		params.push("c=" + collection);
    params.push("b=" + $("#books option:selected").val());
    newParams.push("book=" + $("#books option:selected").val());
    params.push("h=" + $("#hadiths option:selected").val());
    newParams.push("hadith=" + $("#hadiths option:selected").val());
		//Utils.updateSelectedBreadcrumb($("#books option:selected").val() + "/" + $("#hadiths option:selected").val());
		$.getJSON(buildServiceUrl(params, newParams), function(value) {
			if ((hasBooks(collection) && value.book == null) || value.hadith == null) {
				$(document).data("hadithReloadCount", ++reloadCount);
				hadithChanged();
				return;
			}
			if (autoLoading === undefined) {
				// This should not happen but just in case if it's happening
				// we want to track this hadith
				autoLoading = false;
			}
			if (callback == null) {
				callback = function() {
					Bookmark.checkNew($(".not-bookmarked:not(.bookmark-checked)"));
				};
			}
			renderHadith(value);
			// Do time tracking after URL is updated. This is so that correct location is sent to analytics
			sendTimeTakenEvent(loadStartTime, "Hadith");
			loadStartTime = null;
			if (!autoLoading && analyticsRef != null) {
				// If we are loading manually, only then we track it in analytics, otherwise we
				// ignore it
				Analytics.hadith(analyticsRef);
			}
			// We always send page view because it is geniune page view
			Analytics.pageView();
			callback();
		});
	}
	openEmbedCodeDialog = function() {
		Utils.openEmbedCodeDialog('<b>Simple:</b><br><textarea style="width:90%;height:80%">&lt;iframe style="width: 600px; height: 350px;" frameborder="0" src="' + permaLink() + '/embed"&gt;&lt;/iframe&gt;</textarea><br><br><b>With Classification:</b><br><textarea style="width:90%;height:80%">&lt;iframe style="width: 600px; height: 350px;" frameborder="0" src="' + permaLink() + '/embed?classification"&gt;&lt;/iframe&gt;</textarea><br><br><b>You can adjust <i>width</i> and <i>height</i> according to your needs.</b>');
	}
	renderHadith = function(hadithObj) {
		$('#results-text').html("");
		var collection = $("#hadith-collections option:selected").val();
		var collectionText = $("#hadith-collections option:selected").attr("ref-text");
		var hadithTitleForPage = "";
		hadithTitleForPage += collectionText + " ";
		var hadithForTitle = hadithTitleForPage;
		// We have loaded it successfully so we reset the count
		$(document).data("hadithReloadCount", 0);
		if (hasBooks(collection)) {
			hadithTitleForPage += "Book " + hadithObj.book + " ";
		}
		if (hasBookNames(collection)) {
			hadithForTitle += "Book of " + $("#books option:selected").text().substr(3) + " ";
		} else {
			if (hasBooks(collection)) {
				hadithForTitle += "Book " + $("#books option:selected").text() + " ";
			}
		}
		analyticsRef = hadithTitleForPage + "Hadith " + hadithObj.hadith;
		var titleRef = hadithForTitle + "Hadith " + hadithObj.hadith;
		hadithTitleForPage = hadithTitleForPage + "Hadith " + hadithObj.hadith;
		document.title = titleRef + " - amrayn";
		var favImg = "<a class='link-small-icon fa not-bookmarked' style='position:relative;background-position-x:0px;' key='1" + hadithObj.fastIdentifier + "' keyid='1" + hadithObj.keyid + "' btype='Hadith'></a>";
		var htmlHadith = "<h2 class='hadith-title'>" + (IS_EMBEDDED ? "<a href='" + permaLink() + "' style='text-decoration:none'>" : "") + hadithTitleForPage + (IS_EMBEDDED ? "</a>" : "");
		var editHadithBtn = HadithEditor.canEdit ? "<a class='link-small-icon fa fa-edit edit-hadith-btn' title='Edit this hadith' style='position:relative;background-position-x:0px;'></a>" : "";
		var advancedEditHadithBtn = HadithEditor.canEdit ? "<a class='local link-small-icon fa fa-pencil advanced-editor' title='Open in Advanced Editor' style='position:relative;background-position-x:0px;'></a>" : "";
		var saveHadithBtn = HadithEditor.canEdit ? "<a class='link-small-icon fa fa-save save-hadith-btn' title='Save modifications' style='position:relative;background-position-x:0px;'><sup style='color: #ff0000;font-weight: bold;display:none;' class='unsaved-changes-icon'>*</sup></a>" : "";
		htmlHadith = favImg + editHadithBtn + saveHadithBtn + advancedEditHadithBtn + htmlHadith + "</h2>";
		var englishHadith =  hadithObj.text;
		var arabicHadith = typeof hadithObj.textArabic === "undefined" ? "" : nl2br(hadithObj.textArabic);
    if (window.localStorage.getItem('newapi') === 'true') {
      englishHadith = nl2br(englishHadith);
      arabicHadith = nl2br(arabicHadith);
    }
		var arabicTextElement = arabicHadith == "" ? "" : "<div class='hadith-text-arabic arabic'>" + arabicHadith + "</div>";
		htmlHadith += "\
				<ul id='tabs' class='tabs'>\
					<li><a name='#tab1' class='en'>English</a></li>\
					<li><a name='#tab2' class='ar'>Arabic</a></li>\
					<li><a name='#tab3' class='pr'>Proposed&nbsp;&nbsp;<span class='fa fa-info' style='cursor:pointer;background-size: 18px;opacity:0.9;' notooltip title='Proposal Info' onclick='HadithEditor.proposalInfo()'></span>&nbsp;&nbsp;<span class='fa fa-close' style='cursor:pointer;background-size: 18px;opacity:0.9;' notooltip title='Cancel proposal' onclick='HadithEditor.cancelProposal($(this), hadithChanged)'></span></a></li>\
				</ul>\
				<div id='content-tabs' class='content-tabs'>\
    			<div id='tab1'><span class='hadith-text'>" + englishHadith + "</span></div>\
    			<div id='tab2'>" + arabicTextElement + "</div>\
    			<div id='tab3'></div>";
				$('#results-text').append($("<div class='hadith-container'></div>").html(htmlHadith));
				if (!IS_EMBEDDED || (IS_EMBEDDED && SHOW_CLASSIFICATION)) {
					$('#results-text').append(buildGradeLabelsHTML(hadithObj.gradeFlag));
				}
				if (!IS_EMBEDDED) {
					$('#results-text').append("<div class='hadith-note'></div>");
				}
				if (!IS_EMBEDDED || (IS_EMBEDDED && SHOW_REF)) {
					$('#results-text').append(buildCrossReferenceHTML(hadithObj.links));
					$('#results-text').append(buildReferenceHTML(hadithObj.references, hadithObj.volume));
				}
				if (!IS_EMBEDDED) {
					$('#results-text').append("<br><br><div class='hadith-section-label'>Link</div>&nbsp;&nbsp;&nbsp;&nbsp;Page: <span style='text-decoration: none;color: #000;' class='perma-link'><a style='text-decoration: none;' rel='nofollow' href='" + permaLink() + "'>" + permaLink() + "</a></span>");
					$('#results-text').append("<br>&nbsp;&nbsp;&nbsp;&nbsp;<span style='text-decoration: none;color: #000;' class='perma-link'><a style='text-decoration: none;' rel='nofollow' href='#' onclick='javascript:openEmbedCodeDialog();'>Embed Code</a>");
					$('#results-text').append("<div class='review-text'></div>");
				}
		$(".advanced-editor").attr("href", permaLink() + "/edit");
		initializeTabs();
		loadingComplete();
		if (!IS_EMBEDDED) {
			updatePermaUrl();
		}
		HadithEditor.editing = false;
		if (HadithEditor.canEdit) {
			HadithEditor.original = hadithObj;
			$(".hadith-text").dblclick(HadithEditor.editHadith);
			$(".edit-hadith-btn").click(HadithEditor.editHadith);
			$(".save-hadith-btn").click(function() {
				HadithEditor.saveHadith($(this), hadithChanged);
			});
			Utils.buildTooltips();
		}
		if (HadithEditor.canEdit) {
			buildReviewHTML();
		}
		if (User.Preferences.preferArabicHadith && $("#tabs > li:nth(1)").is(":visible")) {
			$("#tabs > li:nth(1) > a").click();
		}
		buildNoteHTML(function(html) {
			$(".hadith-note").html(html);
			bindDeleteHadithNote();
		});

	}
	currentLanguage = function() {
		return parseInt($(".tabs > li > a#current").attr("name").substr(4));
	}
	resetTabs = function(){
		$("#content-tabs > div").hide(); //Hide all content
		$("#tabs a").attr("id",""); //Reset id's
	}
	initializeTabs = function() {

		$("#tabs > li").eq($(this).index()).show();
		$("#content-tabs > div:empty").each(function() {
			$("#tabs > li").eq($(this).index()).hide();
		});

		$("#content-tabs > div").hide(); // Initially hide all content
		$("#tabs li:first a").attr("id","current"); // Activate first tab
		$("#content-tabs > div:first").fadeIn(); // Show first tab content
		$("#tabs a").unbind("click");
		$("#tabs a").on("click",function(e) {
			e.preventDefault();
			if ($(this).attr("id") == "current"){ // detection for current tab
				return;
			} else {
				resetTabs();
				$(this).attr("id", "current"); // Activate this
				$($(this).attr('name')).fadeIn(); // Show content for current tab
			}
			if ($(this).hasClass("ar") && analyticsRef != null && !arabicHadithEventSent) {
				Analytics.hadithArabic(analyticsRef);
				arabicHadithEventSent = true;
			}
			return false;
		});
	}
	$(document).on("click", ".prev-hadith", function() {
		if ($(document).data().hadithLoading == true) {
			return;
		}
		if ($("#hadiths").val() == $("#hadiths>option")[0].value) {
			if ($("#books").val() == $("#books>option")[0].value) {
				// Do nothing we are on first hadith of collection
				return;
			} else {
				$("#books > option:selected").prev('option').prop('selected', true);
				bookChanged();
			}
		} else {
			$('#hadiths > option:selected').prev('option').prop('selected', true);
			hadithChanged();
		}
	});
	$(document).on("click", ".next-hadith", function() {
		if ($(document).data().hadithLoading == true) {
			return;
		}
		if ($("#hadiths").val() == $("#hadiths>option")[$("#hadiths>option").length-1].value) {
			if ($("#books").val() == $("#books>option")[$("#books>option").length-1].value) {
				// Do nothing we are on last hadith of collection
				return;
			} else {
				$("#books > option:selected").next('option').prop('selected', true);
				bookChanged();
			}
		} else {
			$('#hadiths > option:selected').next('option').prop('selected', true);
			hadithChanged();
		}
	});
	param = function(name) {
		name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
		var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
			results = regex.exec(location.search);
		return results == null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
	}

	var criticalControls = ["#hadith-collections", "#span-books", "#span-hadiths", "#volumes", "#books", "#hadiths"];
	loading = function() {
		var container = $("#results-text")[0];
		if (container != undefined) {
			$(document).data().hadithLoading = true;
			$("#results-text")[0].innerHTML = "<center><div class='spinner'><div class='spinner-icon'></div></div></center>";
			for (var i = 0; i < criticalControls.length; ++i) {
				$(criticalControls[i]).css("opacity", "0.8");
				$(criticalControls[i]).attr("disabled", "true");
			}
		}
	}
	loadingComplete = function() {
		//$("#selected-hadith").css({"background-image" : "", "background-repeat" : "", "background-position" : ""});
		//$("#results-text").css("opacity", "1");
		for (var i = 0; i < criticalControls.length; ++i) {
			$(criticalControls[i]).css("opacity", "1");
			$(criticalControls[i]).removeAttr("disabled");
		}
		$(document).data().hadithLoading = false;
	}
	permaLink = function(completeLink) {
		if (completeLink == undefined) {
			completeLink = true;
		}
		var collection = $("#hadith-collections option:selected").val();
		var collectionStr = REF_LINK_NAMES[collection - 1]; // zero-index
		var v = $("#volumes option:selected").val();
		var b = $("#books option:selected").val();
		var h = $("#hadiths option:selected").val();
		var params = [];
		var url = __domain_secure;
		var paramsInit = "/"; //"/hadith/";
		params.push(collectionStr);
		if (hasBooks(collection)) {
			params.push(b);
		}
		params.push(h);
		var finalParam = paramsInit + params.join("/");
		return completeLink ? (url + finalParam) : finalParam;
	}
	updatePermaUrl = function() {
		var finalParam = permaLink(false);
		if (window.history.pushState != undefined && location.href != __domain + finalParam) {
			window.history.pushState({ init: true }, "Title", finalParam);
		}
		$(window).on("popstate", function(e) {
                     var loc = location.href;
                     var h = parseInt(loc.substr(loc.lastIndexOf("/") + 1));
                     loc = loc.substr(0, loc.lastIndexOf("/"));
                     var b = parseInt(loc.substr(loc.lastIndexOf("/") + 1));
                     loc = loc.substr(0, loc.lastIndexOf("/"));
                     var c = REF_LINK_NAMES.indexOf(loc.substr(loc.lastIndexOf("/") + 1)) + 1;
                     if (isNaN(c) || isNaN(b) || isNaN(h)) {
                         history.go(-1);
                         return;
                     }
                     var oldcoll = $("#hadith-collections").val();
                     var oldbook = $("#books").val();
                     var oldhadith = $("#hadiths").val();

                     $("#hadith-collections").val(c);
                     $("#books").val(b);
                     $("#hadiths").val(h);
                     if (c != oldcoll) {
                         collectionChanged();
                     } else if (b != oldbook) {
                         bookChanged();
                     } else if (h != oldhadith) {
                         hadithChanged();
                     }
		});
		$(".context-menu-signin").attr("href", "/signin/?redr=" + location.href);
		$(".context-menu-signout, .link-signout").attr("href", "/signout/?redr=" + location.href);
	}
