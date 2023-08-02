var LOADING_DISABLE_ELEMENTS = [
	"#current-chapter-header", "#chapter-header-hr", "#bism-header-div"
];
var scrollToSelectedVerseSelector = "#quran-div";
var isLoading = false;
var quranFrame = null;
var quranWindow = null;
var loaderChecker = null;
// Load checker is different to loaderChecker
// this is to activate refreshing page if loading
// takes longer than specified time
// see issue #2
var loadChecker = null;
quranFrameLoaded = function() {}
quranFrameRefreshing = function() {}
changeFont = function(incrementValue) {}
increaseFont = function() {}
decreaseFont = function() {}
buildFontSizeParams = function() {}
getSelectedTranslation = function() {}
refreshFrame = function(newValueStart, newValueEnd) {}
triggerTranslation = function(obj) {}
triggerTafsir = function(obj) {}
triggerTransliteration = function(obj) {}
triggerWordByWord = function(obj) {}
translationChanged = function(obj) {}
transliterationChanged = function(obj) {}
tafsirChanged = function(obj) {}
triggerSeparator = function(obj) {}
triggerLabels = function(obj) {}
triggerRukuh = function(obj) {}
styleChanged = function() {}
scriptStyleChanged = function() {}
quranScriptStyleChanged = function() {}
surahChanged = function() {}
updatePermaUrl = function() {}
verseRangeChanged = function(newValueStart, newValueEnd) {}
var scrollWait = null;
var asyncImageLinkWait = null;
verseSelectionChanged = function(newValue, skipStop) {}
scrollToSelectedVerse = function(selection, animate) {}
openPermaLink = function() {}
openPrintFriendlyDialog = function() {}
openWebLink = function() {}
popout = function() {}
openPDFLink = function() {}
generatePdf = function() {}
generateMp3Range = function() {}
getImageLink = function() {}
asyncImageLinkShort = function(callback) {}
openImageLink = function() {}
continuousChanged = function(obj) {}
loadCookies = function() {}
updateVerseClickable = function() {}
refreshDelayChanged = function() {}
memorizationModeChanged = function() {}
readingModeChanged = function() {}
floatingNavigationChanged = function() {}
hideRecitationBarChanged = function() {}
var refreshWait = null;
var refreshTimeStart = null;

var COOKIE_INFO = {
	"continuous" : { "name" : "recite-continuous", "evt" : "onchange", "obj" : "chk-continuous", "defaultValue" : true, "property" : "checked" },
	"scroll-sel" : { "name" : "read-scroll", "evt" : null, "obj" : "chk-selected-verse-scroll", "defaultValue": true, "property" : "checked"  },
	"separator" : { "name" : "read-separator", "evt" : null, "obj" : "chk-separator", "defaultValue": true, "property" : "checked"  },
	"labels" : { "name" : "read-labels", "evt" : null, "obj" : "chk-labels", "defaultValue": true, "property" : "checked"  },
	"rukuh" : { "name" : "read-rukuh", "evt" : null, "obj" : "chk-rukuh", "defaultValue": true, "property" : "checked"  },
	"refresh-delay" : { "name" : "refresh-delay", "evt" : null, "obj" : "refresh-delay", "defaultValue": 0, "property" : "value"  },
	"verse-clickable" : { "name" : "verse-clickable", "evt" : "onchange", "obj" : "chk-verse-clickable", "defaultValue": true, "property" : "checked"  },
	"memorization-mode" : { "name" : "recite-memorization-mode", "evt" : "onchange", "obj" : "chk-mem-mode", "defaultValue": false, "property" : "checked"  },
	"read-quran-script" : { "name" : "read-quran-script", "evt" : null, "obj" : "quran-script-bar-select", "defaultValue": 1, "property" : "value"  },
	"read-quran-font" : { "name" : "read-quran-font", "evt" : null, "obj" : "script-font-bar-select", "defaultValue": DEFAULT_ARABIC_FONT, "property" : "value"  },
	"reading-mode" : { "name" : "read-reading-mode", "evt" : "onchange", "obj" : "chk-reading-mode", "defaultValue": true, "property" : "checked"  },
	"floating-nav-btn" : { "name" : "floating-nav-btn", "evt" : "onchange", "obj" : "chk-floating-nav-btn", "defaultValue": false, "property" : "checked"  },
	"hide-recitation-bar" : { "name" : "recite-hide-recitation-bar", "evt" : "onchange", "obj" : "chk-hide-recitation-bar", "defaultValue": false, "property" : "checked"  }
};

var COOKIE_INFOS = {
	1: "continuous",
	2: "scroll-sel",
	3: "separator",
	4: "labels",
	5: "rukuh",
	6: "verse-clickable",
	7: "refresh-delay",
	8: "memorization-mode",
	9: "read-quran-script",
	10: "read-quran-font",
	11: "reading-mode",
	12: "floating-nav-btn",
	13: "hide-recitation-bar",
	length: 13
};
initializeControls = function() {
	$('#accordion').accordion({
		header: "h4",
		animate: {
			duration: 200,
			heightStyle: "content",
			clearStyle: true
		},
		collapsible: true
	});
	// Refreshes accordion
	$('.menu-contents').css('height', 'auto');

	$("#loader").css("display", "inline-block");
	spinInit("#range-repeat-input, #verse-repeat-input", {
		min: 1
	});
	spinInit("#refresh-delay", {
		min: 0,
		max: 30
	});
	spinInit("#verse-gap-input, #verse-start-pos-input, #verse-end-pos-input, #range-gap-input", {
		min: 0,
		max: 300
	});

	$('.verse-spin-box, #refresh-delay').css({
		'width' : '30px'
	});
	$('.ui-widget button').css({
		'font-size':'14px'
	});
	$('.ui-spinner').css({
		'background-color' : '#fff'
	});
	$('.ui-accordion .ui-accordion-content').css({
		'overflow':'hidden'
	});
	$( "#verse-start-end-pos" ).slider({
		range: true,
		min: 0,
		max: 300,
		animate: "fast",
		values: [ 0, 300 ],
		slide: function( event, ui ) {
			intVal("#verse-start-pos-input", ui.values[0]);
			intVal("#verse-end-pos-input", ui.values[1]);
			$("#verse-start-end-pos-val").text(ui.values[0] + "-" + ui.values[1] + " sec");
		}
	});
	val0 = $("#verse-start-end-pos").slider("values")[0];
	val1 = $("#verse-start-end-pos").slider("values")[1];
	intVal("#verse-start-pos-input", val0);
	intVal("#verse-end-pos-input", val1);
        $("#chk-download-sel-bism").click(function() {
             updatePartMp3Link();
	});
	$("#verse-start-end-pos-val").text(val0 + "-" + val1 + " sec");
	$("#verse-start-end-pos").slider("disable");
	$("#verse-start-end-pos-val").css("color", "#999");
	$( "#seek-bar" ).slider({
		min: 0,
		max: 300,
		value: 0,
		animate: true,
		// pausedBySlider determines whether we paused it or it was
		// already paused when we started to slide.
		alreadyPaused: false,
		start: function(event, ui) {
			if (currentSound != null && !currentSound.paused) {
				pause();
				this.pausedBySlider = true;
			} else {
				this.pausedBySlider = false;
			}
		},
		slide: function( event, ui ) {
			if (currentSound != null) {
				currentSound.setPosition(ui.value * 1000);
			}
		},
		stop: function(event, ui) {
			if (this.pausedBySlider) {
				resume();
				this.pausedBySlider = false;
			} else {
				// It was already paused so we update status
				updateMediaStatus("Paused", true);
			}
		}
	});
	$("#link-download-sel-mp3").click(function() {
		Analytics.resource(this.href);
		return true;
	});
}

initializeFunctions = function() {
	var initializedRecitation = false; // Only once
	$(window).on("hashchange", function() {
		_selectedVerse = parseInt(location.hash == "" || location.hash == "#" ? 0 : location.hash.substr(1));
		if (isNaN(_selectedVerse)) {
			_selectedVerse = 0;
		} else {
			if (_selectedVerse != 0 && _selectedVerse < _verseStart) {
				_selectedVerse = Math.max(_selectedVerse, _verseStart);
			} else if (_selectedVerse != 0 && _selectedVerse > _verseEnd) {
				_selectedVerse = Math.min(_selectedVerse, _verseEnd);
			}
		}
		if (intVal("#verse-selection-select") != _selectedVerse) {
			intVal("#verse-selection-select", _selectedVerse);
			verseSelectionChanged(_selectedVerse);
		}
		// causes onpopupstate
	});
	quranFrameLoaded = function() {
		$("#cbo-surah").val(currentSurah());
		Utils.updateSelectedBreadcrumb(CHAPTER_NAMES_ENGLISH[intVal("#cbo-surah")]);
		$("#quran-intro-link").attr("href", "/quran/intro/" + intVal("#cbo-surah"));

		$("#quran-intro-link").html("Introduction to Surat " + CHAPTER_NAMES_ENGLISH[intVal("#cbo-surah")]);
		var selectedVerse = intVal("#verse-selection-select");
		var selectionComplete = false;
		var verseStart = ayahStart();
		var verseEnd = ayahEnd();

		// If we have first ayah being first ayah of surah then we enable highlighting no ayah
		// otherwise we disable it

		$("#verse-selection-select, #verse-range-from, #verse-range-to").html("");
		for (var i = 1; i <= totalVerses(); ++i) {
			$("#verse-range-from").append("<option value='" + i + "'>" + i + "</option>");
			if (i >= verseStart) {
				$("#verse-range-to").append("<option value='" + i + "'>" + i + "</option>");
			}
		}
		for (var i = verseStart == 1 ? 0 : verseStart; i <= verseEnd; ++i) {
			$("#verse-selection-select").append("<option value='" + i + "'>" + i + "</option>");
		}
		intVal("#verse-range-from", verseStart);
		intVal("#verse-range-to", verseEnd);
		if (verseStart > 1 && selectedVerse < verseStart) {
			// Highlight first ayah in selected range
			intVal("#verse-selection-select", verseStart);
	 	} else if (selectedVerse <= 1) {
			// Highlight no ayah
	 		intVal("#verse-selection-select", 0);
	 	}
		if (selectedVerse > verseEnd) {
			intVal("#verse-selection-select", verseEnd);
		} else if (selectedVerse === null) {
			intVal("#verse-selection-select", _selectedVerse);
		} else {
			intVal("#verse-selection-select", selectedVerse);
		}

		if (initializedRecitation == false) {
			initializedRecitation = setInterval(function() {
				if ($.data(document, 'soundManagerReady')) {
					loadRecitations();
					window.clearInterval(initializedRecitation);
					initializedRecitation = true;
				}
			}, 1000);
		} else {
			if ($.data(document, 'soundManagerReady')) {
				loadRecitations();
			}
		}

		$("#verse-repeat-input").on(spinChangeEvent, function(event, ui) { verseRepeatChanged(this.value); });
		$("#range-repeat-input").on(spinChangeEvent, function(event, ui) { rangeRepeatChanged(this.value); });
		$("#refresh-delay").on(spinChangeEvent, function(event, ui) { refreshDelayChanged(intVal($(this))); });

		$("#verse-repeat-input, #range-repeat-input").on(spinStopEvent, function(event, ui) {
			if (event.keyCode == 13) { $(this).trigger(spinChangeEvent, [event, ui]); $("#cbo-surah").focus(); }
		});
		$("#verse-range-from, #verse-range-to").change(function() {
			verseRangeChanged(intVal("#verse-range-from"), intVal("#verse-range-to"));
		});
		$("#verse-selection-select").change(function() {
			verseSelectionChanged($(this).val());
		});
		if (ayahCount() === 1 || $(".floating-nav").attr("pref-hidden")) {
			$(".floating-nav").hide();
		} else {
			$(".floating-nav").show();
		}
		$(".quran-control").removeAttr("disabled");
		$(".toolbar-button").removeAttr("disabled");
		var selectedSurah = intVal("#cbo-surah");
		$("#emushaf-link").attr("href", getMushafLink());
		$("#intro-link").attr("href", "/quran/intro/" + selectedSurah);
		updateVerseClickable();
		for (var i = 0; i < LOADING_DISABLE_ELEMENTS.length; ++i) {
			$(LOADING_DISABLE_ELEMENTS[i]).css("opacity", "1");
		}
		isLoading = false;
		updatePermaUrl();
		// Do time tracking after URL is updated. This is so that correct location is sent to analytics
		sendTimeTakenEvent(refreshTimeStart, "QuranFrame");
		frameLoadComplete(function() {
			verseSelectionChanged();
			if (loadChecker != null) {
				window.clearInterval(loadChecker);
				loadChecker = null;
			}
			$("#quran-div").css("line-height", "normal");
			// This is so if script or any such thing change that affects view, we are refreshing
			View.refresh();
			Analytics.pageView();
		});
	}
	quranFrameRefreshing = function() {
		$(".top-menu-section-icon").removeClass("top-menu-section-icon-selected");
		$(".top-menu-section-as-menu").removeClass("top-menu-section-as-menu");
		// jQuery html() is slow
		$("#quran-div")[0].innerHTML = "<center><div class='spinner'><div class='spinner-icon'></div></div></center>";
		isLoading = true;
		for (var i = 0; i < LOADING_DISABLE_ELEMENTS.length; ++i) {
			$(LOADING_DISABLE_ELEMENTS[i]).css("opacity", "0.3");
		}
	}
	asyncImageLinkWait = null;
	updateImageShortLinkAfterWaiting = function(wait) {
		if (wait == undefined) wait = 1000;
		if (asyncImageLinkWait != null) { window.clearInterval(asyncImageLinkWait); }
		asyncImageLinkWait = setInterval(function() {
			// We set the link text to be long link
			// and send request to shorten the URL, if its successfully shortened, long
			// link is replaced by shorten.
			var pngLink = getImageLink();
			$("#permalink-value-image").val(pngLink);
			asyncImageLinkShort(function(d) { $("#permalink-value-image").val(d); }, pngLink);
			window.clearInterval(asyncImageLinkWait);
			asyncImageLinkWait = null;
		}, wait);
	}
	changeFont = function(incrementValue) {
		Font.__changeAllBy(incrementValue);
		if ($("#chk-selected-verse-scroll").is(":checked")) {
			scrollToSelectedVerse(undefined, false);
			updatePermaUrl();
		}
		View.refresh();
	}
	increaseFont = function() {
		changeFont(1);
	}
	decreaseFont = function() {
		changeFont(-1);
	}
	getSelectedTranslation = function() {
		var result = {0 : undefined,
					  1 : {"has" : false, "id" : undefined},
					  2 : {"has" : false, "id" : undefined},
					  3 : {"has" : false, "id" : undefined},
					  4 : {"has" : false, "id" : undefined}}; // format result[i]["has"] or result[i]["id"]
		var selectedTranslation = _HIDE_ID;
		var selectedTranslation2 = _HIDE_ID;
		var selectedTranslation3 = _HIDE_ID;
		var selectedTranslation4 = _HIDE_ID;
		var idx1 = -1;
		var idx2 = -1;
		var idx3 = -1;
		var idx4 = -1;
		$("#cbo-translations option:selected").each(function(i, v) {
			switch (i) {
			case 0:
				selectedTranslation = TRANSLATIONS[$(this).val()]["id"];
				idx1 = $(this).val();
				break;
			case 1:
				selectedTranslation2 = TRANSLATIONS[$(this).val()]["id"];
				idx2 = $(this).val();
				break;
			case 2:
				selectedTranslation3 = TRANSLATIONS[$(this).val()]["id"];
				idx3 = $(this).val();
				break;
			case 3:
				selectedTranslation4 = TRANSLATIONS[$(this).val()]["id"];
				idx4 = $(this).val();
				break;
			}
		});
		var hasTranslation = selectedTranslation != _HIDE_ID;
		var hasTranslation2 = hasTranslation && selectedTranslation2 != _HIDE_ID;
		var hasTranslation3 = hasTranslation2 && selectedTranslation3 != _HIDE_ID;
		var hasTranslation4 = hasTranslation3 && selectedTranslation4 != _HIDE_ID;
		result[1]["has"] = hasTranslation;
		result[2]["has"] = hasTranslation2;
		result[3]["has"] = hasTranslation3;
		result[4]["has"] = hasTranslation4;
		result[1]["id"] = selectedTranslation;
		result[2]["id"] = selectedTranslation2;
		result[3]["id"] = selectedTranslation3;
		result[4]["id"] = selectedTranslation4;
		result[1]["idx"] = idx1;
		result[2]["idx"] = idx2;
		result[3]["idx"] = idx3;
		result[4]["idx"] = idx4;
		return result;
	}
	refreshWait = null;
	buildEmbedUrl = function(newValueStart, newValueEnd) {
      	 var wordByWordBox = $("#chk-word-by-word");
      	 var selectedTranslation = 0;
      	 var selectedTranslation2 = 0;
      	 var selectedTranslation3 = 0;
      	 var selectedTranslation4 = 0;
      	 var selectedTransliteration = $("#cbo-transliteration option:selected");
      	 var selectedTafsir = $("#cbo-tafsir option:selected");
      	 var selectedSurah = $("#cbo-surah option:selected");
      	 var selectedStyle = $("#style-bar-select option:selected").val();
      	 if (selectedStyle == 122) {
      	 	$("#style-bar-select").val(2);
      		selectedStyle = 2;
          }
      	 var verseStart = newValueStart == undefined ? intVal("#verse-range-from") : newValueStart;
      	 var verseEnd = newValueEnd == undefined ? intVal("#verse-range-to") : newValueEnd;
      	 var params = [];
      	 params.push(buildFontSizeParams());

      	 var translationArr = getSelectedTranslation();
      	 params.push("tra=" + (translationArr[1]["has"] ? 1 : 0));
      	 params.push("tra2=" + (translationArr[2]["has"] ? 1 : 0));
      	 params.push("tra3=" + (translationArr[3]["has"] ? 1 : 0));
      	 params.push("tra4=" + (translationArr[4]["has"] ? 1 : 0));
      	 var translationsParam = "tid=";
      	 if (translationArr[1]["has"] && translationArr[4]["id"] != undefined) {
      		 translationsParam += translationArr[1]["id"] + ",";
      	 }
      	 if (translationArr[2]["has"] && translationArr[2]["id"] != undefined) {
      		 translationsParam += translationArr[2]["id"] + ",";
      	 }
      	 if (translationArr[3]["has"] && translationArr[3]["id"] != undefined) {
      		 translationsParam += translationArr[3]["id"] + ",";
      	 }
      	 if (translationArr[4]["has"] && translationArr[4]["id"] != undefined) {
      		 translationsParam += translationArr[4]["id"];
      	 }
      	 params.push(translationsParam);
      	 params.push("taf=" + (selectedTafsir.val() != _HIDE_ID ? "1" : "0"));
      	 if (selectedTafsir.val() != _HIDE_ID) {
      		 params.push("tafid=" + TAFSIRS[selectedTafsir.val()]["id"]);
      	 }
      	 params.push("tri=" + (selectedTransliteration.val() != _HIDE_ID ? "1" : "0"));
      	 if (selectedTransliteration.val() != _HIDE_ID) {
      		 params.push("triid=" + TRANSLITERATIONS[selectedTransliteration.val()]["id"]);
      	 }
      	 var scriptType = $("#script-font-bar-select option:selected").val();
      	 if (scriptType == 3) {
      		scriptType = DEFAULT_ARABIC_FONT;
      		params.push("hidescript");
      	 }
      	 var quranScriptStyle = $("#quran-script-bar-select option:selected").val();
      	 if (quranScriptStyle == 0) {
      		$("#quran-script-bar-select").val(1);
      		quranScriptStyle = 1;
      	 }
      	 params.push("qscrid=" + quranScriptStyle);
      	 params.push("scr=" + scriptType);
      	 params.push("s=" + selectedSurah.val());
      	 params.push("v=" + verseStart + "-" + verseEnd);
      	 params.push("style=" + selectedStyle);
      	 if (wordByWordBox.is(":checked")) {
      		 params.push("wrd=1");
      	 }
      	 if (!$("#chk-separator").is(":checked")) {
      		 params.push("nosep");
      	 }
      	 if (!$("#chk-labels").is(":checked")) {
      		 params.push("nolabels");
      	 }
      	 if (!$("#chk-rukuh").is(":checked")) {
      		 params.push("norukuh");
      	 }
      	 params.push("fnclr");
      	 if (selectedSurah.val() != 1 && selectedSurah.val() != 9) {
      		 // No bismillah for fatiha or tauba
      		 params.push("bism");
       	}
      	 params.push("title");
      	 params.push("title-mean");
      	 return baseWidget + (params.join("&"));
	}
	refreshFrame = function(newValueStart, newValueEnd, wait, force) {
		if (isLoading) {
			return;
		}
		if (wait == undefined) {
			wait = true;
		}
		if (force == undefined) {
			force = false;
		}
		if (refreshWait != null) {
			window.clearInterval(refreshWait);
		}
		refreshWait = setInterval(function() {
			 var finalSrc = buildEmbedUrl(newValueStart, newValueEnd) + __svcargs_no_ver_mid;
			 if (force || $("#quran-div").attr("src") != finalSrc) {
				refreshTimeStart = new Date();
			 	quranFrameRefreshing();
			 	$(".quran-control").attr("disabled", "true");
			 	$(".toolbar-button").attr("disabled", "true");
				updateMediaStatus("Waiting...", false);
				$("#quran-div").load(finalSrc, quranFrameLoaded);
				$("#quran-div").attr("src", finalSrc);
			 	updatePermaUrl();
				// see issue #2
				if (loadChecker != null) {
					window.clearInterval(loadChecker);
				}
				loadChecker = setInterval(function() {
					Utils.transitionPageLoad(location.href);
					window.clearInterval(loadChecker);
					loadChecker = null;
				}, 10000);
			 }
			 window.clearInterval(refreshWait);
			 refreshWait = null;
		}, wait ? (intVal("#refresh-delay") * 1000) : 0);
	}

	updateVerseClickable = function() {
		for (var i = intVal("#verse-range-from"); i <= intVal("#verse-range-to"); ++i) {
			var elems = $("#quran-div").find(".ayah-" + i);
			elems.css("cursor", "auto");
			elems.unbind("click");
			if ($("#chk-verse-clickable").is(":checked")) {
				elems.css("cursor", "pointer");
				elems.click(function() {
					var classNames = $(this).attr("class").split(" ");
					if (classNames.indexOf("highlighted-ayah") == -1) {
						var v = parseInt(classNames[0].substr(classNames[0].lastIndexOf("-") + 1));
						intVal("#verse-selection-select", v);
						verseSelectionChanged();
					}
			   });
			}
		}
		var cookieInfo = COOKIE_INFO["verse-clickable"];
		saveCookie(cookieInfo.name, $("#chk-verse-clickable").is(":checked"));
	}

	triggerWordByWord = function(obj) {
		refreshFrame();
		Analytics.quran("ViewWordByWord", obj.checked);
	}
	translationChanged = function(obj) {
		$("#cbo-translations").trigger("chosen:updated");
		$(".chosen-choices").click();
		refreshFrame();
		Analytics.quran("TranslationChanged", $("#cbo-translations option:selected").map(function () {
        												return $(this).text();
   	 									   		}).get().join(', ')
		);
	}
	transliterationChanged = function(obj) {
		refreshFrame();
		Analytics.quran("TransliterationChanged", $("#cbo-transliteration option:selected").text());
	}
	tafsirChanged = function(obj) {
		refreshFrame();
		Analytics.quran("TafsirChanged", $("#cbo-tafsir option:selected").text());
	}
	triggerSeparator = function(obj) {
		refreshFrame();
		Analytics.quran("SeparatorChanged", obj.checked);
		var cookieInfo = COOKIE_INFO["separator"];
		saveCookie(cookieInfo.name, obj.checked);
	}
	triggerLabels = function(obj) {
		if (obj.checked) {
			$(".resource-label").removeClass("hide-feature");
		} else {
			$(".resource-label").addClass("hide-feature");
		}
		Analytics.quran("LabelsChanged", obj.checked);
		var cookieInfo = COOKIE_INFO["labels"];
		saveCookie(cookieInfo.name, obj.checked);
		updateEmbedCode();
	}
	triggerRukuh = function(obj) {
		if (obj.checked) {
			$(".rukuh").removeClass("hide-feature");
		} else {
			$(".rukuh").addClass("hide-feature");
		}
		Analytics.quran("RukuhChanged", obj.checked);
		var cookieInfo = COOKIE_INFO["rukuh"];
		saveCookie(cookieInfo.name, obj.checked);
		updateEmbedCode();
	}
	styleChanged = function() {
		if ($("#style-bar-select").val() == 122) {
			$("#style-bar-select").val(2);
		}
		refreshFrame();
		Analytics.quran("ReadStyleChanged", $("#style-bar-select option:selected").text());
	}
	scriptStyleChanged = function() {
		refreshFrame();
		Analytics.quran("ScriptStyleChanged", $("#script-font-bar-select option:selected").text());
		var cookieInfo = COOKIE_INFO["read-quran-font"];
		saveCookie(cookieInfo.name, $("#script-font-bar-select option:selected").val());
	}
	quranScriptStyleChanged = function() {
		if ($("#quran-script-bar-select").val() == 0) {
			$("#quran-script-bar-select").val(1);
		}
		refreshFrame();
		Analytics.quran("QuranScriptStyleChanged", $("#quran-script-bar-select option:selected").text());
		var cookieInfo = COOKIE_INFO["read-quran-script"];
		saveCookie(cookieInfo.name, $("#quran-script-bar-select option:selected").val());
	}
	surahChanged = function() {
		// Highlight no ayah on surah change

		var selectedSurah = intVal("#cbo-surah");
		$("#quran-intro-link").attr("href", "/quran/intro/" + selectedSurah);
		$("#quran-intro-link").html("Introduction to Surat " + CHAPTER_NAMES_ENGLISH[selectedSurah]);


		$("#verse-selection-select, #verse-range-from, #verse-range-to").html("");
		for (var i = 0; i <= VERSE_COUNT[selectedSurah]; ++i) {
			if (i > 0) {
				$("#verse-range-from").append("<option value='" + i + "'>" + i + "</option>");
				$("#verse-range-to").append("<option value='" + i + "'>" + i + "</option>");
			}
			$("#verse-selection-select").append("<option value='" + i + "'>" + i + "</option>");
		}
		intVal("#verse-range-from", 1);
		intVal("#verse-range-to", VERSE_COUNT[selectedSurah]);

		refreshFrame(1, VERSE_COUNT[selectedSurah]);
		updatePermaUrl();
	}
	updatePermaUrl = function() {
		var selectedSurah = intVal("#cbo-surah");
		var verseStart = intVal("#verse-range-from");
		var verseEnd = intVal("#verse-range-to");
		var selectedVerse = intVal("#verse-selection-select");
		var selectedStyle = $("#style-bar-select option:selected").val();
		var scriptType = $("#script-font-bar-select option:selected").val();
		var tafsirId = $("#cbo-tafsir option:selected").val();
		var reciterId = $("#cbo-reciters option:selected").val();
		var translationArr = getSelectedTranslation();
		var translationOn = translationArr[1]["has"];
		var translation2On = translationArr[2]["has"];
		var translation3On = translationArr[3]["has"];
		var translation4On = translationArr[4]["has"];
		var translationId = translationArr[1]["id"];
		var translationId2 = translationArr[2]["id"];
		var translationId3 = translationArr[3]["id"];
		var translationId4 = translationArr[4]["id"];
		var transliterationId = $("#cbo-transliteration option:selected").val();
		var tafsirOn = tafsirId != _HIDE_ID;
		var transliterationOn = transliterationId != _HIDE_ID;
		var wordByWordOn = $("#chk-word-by-word").is(":checked");
		var separatorOff = !$("#chk-separator").is(":checked");
		var labelsOff = !$("#chk-labels").is(":checked");
		var surahRef = selectedSurah;
		var hasChosenVerses = verseStart > 1 || verseEnd < totalVerses();
		if (hasChosenVerses) {
			surahRef += "/" + verseStart;
			if (verseStart < verseEnd) {
				surahRef += "-" + verseEnd;
			}
		}
		var finalUrl = "/" + surahRef;
		const newExpUrlElements = [];
		var urlElements = [];
		if (selectedStyle != 2) {
			urlElements.push("sty=" + selectedStyle);
		}
		if (scriptType == 3) {
			urlElements.push("hidescript");
		}
		if (reciterId != undefined && RECITERS[reciterId].id != DEFAULT_RECITER) {
			urlElements.push("rec=" + RECITERS[reciterId].id);
		}
		newExpUrlElements.push(`reciter=${RECITERS[reciterId].id}`);
	 	var translationsParam = [];
		// If translation is on and not same as default or translation is on with any other translation
	 	if (translationOn && DEFAULT_TRANSLATIONS[0] != translationId) {
		 	translationsParam[translationsParam.length] = translationId;
	 	}
	 	if (translation2On) {
		 	translationsParam[translationsParam.length] = translationId2;
	 	}
	 	if (translation3On) {
		 	translationsParam[translationsParam.length] = translationId3;
	 	}
	 	if (translation4On) {
		 	translationsParam[translationsParam.length] = translationId4;
	 	}
		if (translationOn && (translation2On || translation3On || translation4On) && !_.contains(translationsParam, translationId)) {
			translationsParam[translationsParam.length] = translationId;
		}

		if (!translationOn && !translation2On && !translation3On && !translation4On) {
			urlElements.push("notrans");
		} else {
			translationsParam = _.uniq(translationsParam);
			if (translationsParam.length > 0) {
				var translationsParamCsv = translationsParam.join(",");
		 		urlElements.push("tid=" + translationsParamCsv);
				newExpUrlElements.push(`tid=${translationsParamCsv}`);
			}
		}
		if (tafsirOn && tafsirId != undefined && TAFSIRS[tafsirId]["id"] != DEFAULT_TAFSIR) {
			urlElements.push("tafid=" + TAFSIRS[tafsirId]["id"]);
			newExpUrlElements.push(`tafid=${TAFSIRS[tafsirId]["id"]}`);
		}
		if (wordByWordOn) {
			urlElements.push("wrd=1");
			newExpUrlElements.push(`wrdid=198`);
		}
		if (transliterationOn && transliterationId != undefined && TRANSLITERATIONS[transliterationId]["id"] != DEFAULT_TRANSLITERATION) {
			urlElements.push("triid=" + TRANSLITERATIONS[transliterationId]["id"]);
			newExpUrlElements.push(`triid=${TRANSLITERATIONS[transliterationId]["id"]}`);
		}
		for (i = 0; i < urlElements.length; ++i) {
			finalUrl += (i == 0 ? "?" : "&") + urlElements[i];
		}
		// Dont use location.hash to preserve state
		if (selectedVerse != 0 && selectedVerse > verseStart && selectedVerse <= verseEnd) {
			finalUrl += "#" + selectedVerse;
		}
		if ($("#chk-update-url").length === 0 || $("#chk-update-url").is(":checked")) {
			if (window.history.replaceState != undefined) {
				if (finalUrl.indexOf("?") == finalUrl.length - 1) {
					finalUrl = finalUrl.substr(0, finalUrl.length - 1);
				}
				if (location.href != location.origin + finalUrl) {
						//$(window).on("popstate", function(e) {
						//if ($("body").hasClass("historypushed")) {
						//window.location = location.href;
						//}
						//});
					window.history.replaceState("object", "Title", finalUrl);
					//$("body").addClass("historypushed");
				}
			} else {
				$("#chk-update-url").prop("checked", false);
				$("#chk-update-url").attr("disabled", true);
				$("#chk-update-url-label").attr("disabled", true);
				$("#chk-update-url-label").html("Auto update address (Not supported by browser)");
			}
		}
		var surahRefWithName = CHAPTER_NAMES_ENGLISH[selectedSurah] + " " + selectedSurah;
		var newTitle = CHAPTER_NAMES_ENGLISH[selectedSurah];
		if (verseStart > 1 || verseEnd < totalVerses()) {
			newTitle += " " + selectedSurah;
			newTitle += ":" + verseStart;
			if (verseStart < verseEnd) {
				newTitle += "-" + verseEnd;
			}
		}
		newTitle += " - Noble Quran";
		$("#keywords-meta").attr("content", surahRefWithName);
		$("#name-meta").attr("content", newTitle);
		$("#title-meta").attr("content", newTitle + " - Amrayn");
		document.title = $("#title-meta").attr("content");
		var fullLink = "http://amrayn.com" + finalUrl;
		$("#permalink-value").val(fullLink);

		verseStart = verseStart || 1;
		var newExpLink = `http://amrayn.com/quran/${selectedSurah}/${Math.min(verseStart, totalVerses())}?utm_source=legacy&_trts=newexp&tab=8${verseStart === verseEnd || !verseEnd || (verseStart === 1 && verseEnd === totalVerses()) ? '' : `&range=${verseStart}-${verseEnd}`}&${newExpUrlElements.join('&')}`;
		$(".new-exp-link-a").attr("href", newExpLink);
		updateEmbedCode();
		$("#permalink-value-embed-image").val("<a href='" + fullLink + "' style='text-decoration:none;border:none;outline:none;' target='_blank'><img style='border:none;outline:none;' src='" + getImageLink() + "' /" + "></a>"); // because of stupid editor :p

	}
	updateEmbedCode = function() {
		var src = buildEmbedUrl();
		src = src.replace("embed-local", "embed");
		src += "&" + buildFontSizeParams();
		var fullEmbedCode = '<iframe style="width: 100%; max-height: 50em; height: 50em;" frameborder="0" name="quran-frame" src="' + src + '">iframe is not supported by your browser.</iframe>';
		$("#permalink-value-embed").val(fullEmbedCode);
	}
	verseRangeChanged = function(newValueStart, newValueEnd) {
		refreshFrame(newValueStart, newValueEnd);
	}
	scrollWait = null;
	verseSelectionChanged = function(newValue, skipStop) {
		if (skipStop === undefined || !skipStop) {
			// stop();
		}
		var selection = newValue == undefined ? intVal("#verse-selection-select") : newValue;
		if (selection == "0") {
			unhighlightAll();
		} else {
			if (intVal("#verse-range-from") != intVal("#verse-range-to")) {
				if ($("#chk-selected-verse-scroll").is(":checked")) {
					if (scrollWait != null) { window.clearInterval(scrollWait); }
					scrollWait = setInterval(function() {
						scrollToSelectedVerse(selection);
						window.clearInterval(scrollWait);
						scrollWait = null;
					}, 100);
				}
				highlightAyah(selection);
			}
		}
		if (typeof loadCurrentSelection === "function" && currentSound != null) {
			// we are checking currentSound != null so we know user is interested in recitation
			loadCurrentSelection();
		}
		$("#emushaf-link").attr("href", getMushafLink());

		updatePartMp3Link();
		updatePermaUrl();
	}
	scrollToSelectedVerse = function(selection, animate) {
		var readingMode = $("#chk-reading-mode").is(":checked");
		if (selection == undefined) {
			selection = intVal("#verse-selection-select");
		}
		//$("#quran-div").scrollTop(0);
		var elements = $("html, body").find(".ayah-" + selection + ":visible");
		if (elements != undefined && elements.position() != undefined) {
			var animation = animate == undefined || animate;
			var elementTop = /*$(scrollToSelectedVerseSelector).scrollTop() + */elements.position().top;
			elementTop -= readingMode ? 25 : 150;
			if ($("#chk-labels").is(":checked")) {
				// elementTop -= 16;
			}
			// This is so we start from 0 to position
			if (animation) {
				$(scrollToSelectedVerseSelector).animate(
					{ scrollTop: elementTop },
					{ duration: 'fast', easing: 'swing' }
				);
			} else {
				$(scrollToSelectedVerseSelector).scrollTop(elementTop);
			}
		}
		closeAllTooltips();
	}
	//quranFrame = $('#quran-frame');
	//quranWindow = quranFrame[0].contentWindow;

	$("#cbo-translations").attr("multiple", "");

	for (var i = 0; i < TRANSLATIONS.length; ++i) {
		var opt = $('<option></option>').val(i).text(TRANSLATIONS[i]["name"]);
		$("#cbo-translations").append(opt);
	}
	sortSelectByName("#cbo-translations", false);
	var selectedCount = 0;
	for (var i = 0; i < $("#cbo-translations option").length; ++i) {
		if (TRANSLATIONS[i]["id"] == TRANSLATION_IDS[0] ||
			(SHOW_TRANSLATIONS[1] && TRANSLATIONS[i]["id"] == TRANSLATION_IDS[1]) ||
			(SHOW_TRANSLATIONS[2] && TRANSLATIONS[i]["id"] == TRANSLATION_IDS[2]) ||
			(SHOW_TRANSLATIONS[3] && TRANSLATIONS[i]["id"] == TRANSLATION_IDS[3])) {
				$("#cbo-translations option[value='" + i + "']").prop("selected", "true");
				selectedCount++;
				if (selectedCount >= 4) {
					// To save overhead
					break;
				}
		}
	}
	var opt = $('<option></option>').val(_HIDE_ID).text(" - Transliteration : None - ");
	opt.attr("selected", "true");
	$("#cbo-transliteration").append(opt);
	for (var i = 0; i < TRANSLITERATIONS.length; ++i) {
		var opt = $('<option></option>').val(i).text(TRANSLITERATIONS[i]["name"]);
		if (TRANSLITERATIONS[i]["id"] == transliterationID) {
			opt.attr("selected", "true");
		}
		$("#cbo-transliteration").append(opt);
	}
	sortSelectByName("#cbo-transliterations", false);
	var opt = $('<option></option>').val(_HIDE_ID).text(" - Tafsir : None - ");
	opt.attr("selected", "true");
	$("#cbo-tafsir").append(opt);
	for (var i = 0; i < TAFSIRS.length; ++i) {
		var opt = $('<option></option>').val(i).text(TAFSIRS[i]["name"]);
		if (TAFSIRS[i]["id"] == tafsirID) {
			opt.attr("selected", "true");
		}
		$("#cbo-tafsir").append(opt);
	}
	sortSelectByName("#cbo-tafsir", false);
	for (var i = 0; i < RECITERS.length; ++i) {
		var opt = $('<option></option>').val(i).text(RECITERS[i].name)
		if (RECITERS[i].id == _reciterID) {
			opt.attr("selected", "true");
		}
		$("#cbo-reciters").append(opt);
	}
	sortSelectByName("#cbo-reciters", false);
	$("#cbo-translations").on("change", function() {
	    translationChanged(this);
	});
	$("#cbo-reciters").on("change", function() {
		updatePartMp3Link();
	});
	multiSelectInit("#cbo-translations", {
		no_results_text: "No translation found",
		max_selected_options: 4,
		placeholder: 'Please select translation',
		csvDispCount: 3,
	});
	selectInit("#cbo-transliteration, #cbo-tafsir, #cbo-reciters, #cbo-surah, #style-bar-select, #script-font-bar-select, #quran-script-bar-select", {});

	$("#cbo-surah>option[value='" + _surah + "']").attr("selected", true);
	// On some systems this is needed instead.
	$("#cbo-surah").val(_surah);
	intVal("#verse-range-from", _verseStart);

	intVal("#verse-range-to", _verseEnd);
	intVal("#verse-selection-select", _selectedVerse);
	intVal("#verse-repeat-input", 1);
	intVal("#range-repeat-input", 1);
	$("#style-bar-select>option[value='" + readStyle + "']").attr("selected", true);
	$("#chk-selected-verse-scroll").click(function() {
		if ($(this).is(":checked")) {
			scrollToSelectedVerse();
		}
		var cookieInfo = COOKIE_INFO["scroll-sel"];
		saveCookie(cookieInfo.name, $(this).is(":checked"));
	});
	continuousChanged = function(obj) {
		var cookieInfo = COOKIE_INFO["continuous"];
		saveCookie(cookieInfo.name, obj.checked);
	}
	refreshDelayChanged = function(value) {
		var cookieInfo = COOKIE_INFO["refresh-delay"];
		saveCookie(cookieInfo.name, value);
	}
	memorizationModeChanged = function(obj) {
		if (obj.checked) {
			$(".memorization-only").show();
			$(".bottom-menu").css("height", "115px");

			if ($("#chk-hide-recitation-bar").is(":checked")) {
				$("#chk-hide-recitation-bar").click();
			}
		} else {
			$(".memorization-only").hide();
			$(".bottom-menu").css("height", "80px");
		}

		var cookieInfo = COOKIE_INFO["memorization-mode"];
		saveCookie(cookieInfo.name, obj.checked);
	}
	readingModeChanged = function(obj) {
		var cookieInfo = COOKIE_INFO["reading-mode"];
		if (obj.checked) {
			saveCookie(cookieInfo.name, obj.checked);
		} else {
			removeCookie(cookieInfo.name);
		}
		showAppropriateMenu();
	}
	floatingNavigationChanged = function(obj) {
		var cookieInfo = COOKIE_INFO["floating-nav-btn"];
		if (obj.checked) {
			saveCookie(cookieInfo.name, obj.checked);
			$(".floating-nav").attr("pref-hidden", true).hide();
		} else {
			removeCookie(cookieInfo.name);
			$(".floating-nav").removeAttr("pref-hidden").show();
		}
	}
	hideRecitationBarChanged = function(obj) {

		var cookieInfo = COOKIE_INFO["hide-recitation-bar"];
		if (obj.checked) {
			$(".bottom-menu").hide();
			saveCookie(cookieInfo.name, obj.checked);
			$("#scroll-to-top").css("bottom", "4em");
			$(".floating-nav").css("bottom", "3.3em");
			if ($("#chk-mem-mode").is(":checked")) {
				$("#chk-mem-mode").click();
			}
		} else {
			$(".bottom-menu").show();
			removeCookie(cookieInfo.name);
			$("#scroll-to-top").css("bottom", "8em");
			$(".floating-nav").css("bottom", "7.3em");
		}
	}
	loadCookies = function() {
		for (var i = 1; i <= COOKIE_INFOS.length; ++i) {
			var cookieInfo = COOKIE_INFO[COOKIE_INFOS[i]];
			$("#" + cookieInfo.obj).prop(cookieInfo.property, parseValue(cook(cookieInfo.name, cookieInfo.defaultValue)));
			if (cookieInfo.evt != undefined && cookieInfo.evt != null) {
				$("#" + cookieInfo.obj).trigger(cookieInfo.evt);
			}
			if (cookieInfo.func != undefined && cookieInfo.func != null) {
				//var func = new Function(cookieInfo.func);
				cookieInfo.func($("#" + cookieInfo.obj).prop(cookieInfo.property));
			}
		}
		View.readFromCookie();
		View.refresh();
		scrollToSelectedVerse();
	}
	loadCookies();
	$("#next-ayah-float").click(function() {
		if ($('#verse-selection-select > option:selected').length === 0) {
			$('#verse-selection-select > option:first').prop('selected', true);
		}
		$('#verse-selection-select > option:selected').next('option').prop('selected', true);
		verseSelectionChanged();
		return false;
	});
	$("#prev-ayah-float").click(function() {
		if ($('#verse-selection-select > option:selected').length === 0) {
			$('#verse-selection-select > option:first').prop('selected', true);
		}
		$('#verse-selection-select > option:selected').prev('option').prop('selected', true);
		verseSelectionChanged();
		return false;
	});
	shortcut.add("SHIFT+UP", function() {
		if ($('#verse-selection-select > option:selected').length === 0) {
			$('#verse-selection-select > option:first').prop('selected', true);
		}
		$('#verse-selection-select > option:selected').prev('option').prop('selected', true);
		verseSelectionChanged();
	});
	shortcut.add("SHIFT+RIGHT", function() {
		if ($('#verse-selection-select > option:selected').length === 0) {
			$('#verse-selection-select > option:first').prop('selected', true);
		}
		$('#verse-selection-select > option:selected').next('option').prop('selected', true);
		verseSelectionChanged();

	});
	shortcut.add("SHIFT+DOWN", function() {
		if ($('#verse-selection-select > option:selected').length === 0) {
			$('#verse-selection-select > option:first').prop('selected', true);
		}
		$('#verse-selection-select > option:selected').next('option').prop('selected', true);
		verseSelectionChanged();
	});
	shortcut.add("SHIFT+LEFT", function() {
		if ($('#verse-selection-select > option:selected').length === 0) {
			$('#verse-selection-select > option:first').prop('selected', true);
		}
		$('#verse-selection-select > option:selected').prev('option').prop('selected', true)
		verseSelectionChanged();
	});
	// DO NOT CHANGE THIS TO NATURAL CALLBACK
	shortcut.add("SHIFT+P", function() { startReciting() });
	shortcut.add("SHIFT+S", function() { stop() });
	shortcut.add("SHIFT+O", function() { restart() });
}

buildFontSizeParams = function() {
	var params = [];
	params.push("ofs=" + Font.originalSize());
	params.push("efs=" + Font.englishSize());

	var translationArr = getSelectedTranslation();
	var translationOn = translationArr[1]["has"];
	var translation2On = translationArr[2]["has"];
	var translation3On = translationArr[3]["has"];
	var translation4On = translationArr[4]["has"];
	var translationId = translationArr[1]["idx"];
	var translationId2 = translationArr[2]["idx"];
	var translationId3 = translationArr[3]["idx"];
	var translationId4 = translationArr[4]["idx"];

	var translationSize = Font.translation1Size();
	var translationSize2 = Font.translation2Size();
	var translationSize3 = Font.translation3Size();
	var translationSize4 = Font.translation4Size();
	params.push("tfs1=" + translationSize);
	params.push("tfs2=" + translationSize2);
	params.push("tfs3=" + translationSize3);
	params.push("tfs4=" + translationSize4);


	var selectedTransliterationSize = Font.transliterationSize();
	params.push("trifs=" + selectedTransliterationSize);

	var selectedTafsirSize = Font.tafsirSize();
	params.push("taffs=" + selectedTafsirSize);

	return params.join("&");
}

openPermaLink = function() {
	var dialogSelector = $("#permalink-dialog");
	openDialog(dialogSelector, "quran/dialogs/permalink-dialog", function() {
		updateImageShortLinkAfterWaiting();
		$("#permalink-value-pdf").val(getPDFLink());
		updatePermaUrl();
	}, function() {
		$("#permalink-value").select();

		if (typeof hideMobileMenu === "function") {
			hideMobileMenu();
		}
	});
}
openPrintFriendlyDialog = function() {
	var dialogSelector = $("#print-friendly-dialog");
	openDialog(dialogSelector, "quran/dialogs/print-friendly-dialog", function() {
		var selectedSurah = intVal("#cbo-surah");
		var verseStart = intVal("#verse-range-from");
		// No bismillah for tawba or fatiha first verses
		if (selectedSurah == 9 && verseStart == 1) {
			$("#chk-print-friendly-bismillah").prop("checked", false);
			$("#chk-print-friendly-bismillah").attr("disabled", true);
			$("#label-print-friendly-bismillah").text("Bismillah - Not applicable 'At Tawba (9)' first ayahs");
			//height += 20;
		} else {
			$("#chk-print-friendly-bismillah").prop("checked", true);
			$("#chk-print-friendly-bismillah").removeAttr("disabled");
			$("#label-print-friendly-bismillah").text("Bismillah");
		}
	}, function() {
		if (typeof hideMobileMenu === "function") {
			hideMobileMenu();
		}
	});
}
openPunctuationRulesDialog = function() {
	var dialogSelector = $("#punctuation-rules-dialog");
	openDialog(dialogSelector, "quran/dialogs/punctuation-dialog", function() {}, function() {
		if (typeof hideMobileMenu === "function") {
			hideMobileMenu();
		}
	});
}
openMemorizationTipsDialog = function() {
	var dialogSelector = $("#memorization-tips-dialog");
	openDialog(dialogSelector, "quran/dialogs/memorization-dialog", function() {}, function() {
		if (typeof hideMobileMenu === "function") {
			hideMobileMenu();
		}
	});
}
openWebLink = function() {
	var src = "http://amrayn.com" + $("#quran-div").attr("src");
	src = src.replace("/embed-local", "/quran/print");
	src = src.replace("&title-mean", "");
	src = src.replace("&title", "");
	src = src.replace("&bism", "");
	src = src.replace("&fnclr", "");
	if (!$("#chk-print-friendly-ref").is(":checked")) {
		// default embed has ref=0
		src += "&ref=0";
	}
	src += "&" + buildFontSizeParams();
	if ($("#chk-print-friendly-title").is(":checked")) {
		src += "&title";
	}
	if ($("#chk-print-friendly-title-mean").is(":checked")) {
		src += "&title-mean";
	}
	if ($("#chk-print-friendly-bismillah").is(":checked")) {
		src += "&bism";
	}
	if ($("#chk-print-friendly-expand-footnotes").is(":checked")) {
		src += "&exfn";
	}
	if ($("#chk-print-friendly-colorful-footnotes").is(":checked")) {
		src += "&fnclr";
	}
	$("#print-friendly-dialog").dialog("close");
	window.open(src);
}

popout = function() {
	var src = "http://amrayn.com" + $("#quran-div").attr("src");
	src = src.replace("/embed-local", "/quran/print");
	src += "&title";
	src += "&title-mean";
	src += "&" + buildFontSizeParams();
	var selectedSurah = intVal("#cbo-surah");
	var verseStart = intVal("#verse-range-from");
	if (!((selectedSurah == 1 || selectedSurah == 9) && verseStart == 1)) { // No bism for fatiha and tawba first ayahs
		src += "&bism";
	}
	src += "&fnclr";
	window.open(src);
}

getPDFLink = function() {
	var src = $("#quran-div").attr("src");
	src = src.replace("/embed-local?", "");
	src += "&" + buildFontSizeParams();
	src = encodeURIComponent(src);
	var surahName = CHAPTER_NAMES_ENGLISH[intVal("#cbo-surah")];
	var layout = "portrait";//$('input[name=chk-print-friendly-layout]:checked').val();
	var pn = 1;//$("#chk-print-friendly-page-numbers").is(":checked") ? "1" : "0"; // Doesnt work yet
	var params = [];
	params.push("u=" + src);
	params.push("o=" + surahName.replace(" ","-"));
	params.push("s=" + surahName);
	params.push("layout=" + layout);
	params.push("pn=" + pn);
	return __domain_secure + "/quran/pdf?" + params.join("&");
}
generatePdf = function() {
	NProgress.start();
    Utils.notifyUser({text: "Generating PDF...", timeout: "5000", type:"success"});
	Analytics.quran("Generate PDF", CHAPTER_NAMES_ENGLISH[intVal("#cbo-surah")]);
    location.href = getPDFLink();
   	NProgress.done();
}
generateMp3Range = function() {
	var link = getSelectionMp3Link();
	if (link === '#') {
		Utils.notifyUser({text: 'Please select maximum 50 ayahs', timeout: 5000, type:"error"});
		return;
	}
	NProgress.start();
    Utils.notifyUser({text: "Generating Mp3...", timeout: "5000", type:"success"});
	Analytics.quran("Generate Range Mp3", link);
    location.href = link;
   	NProgress.done();

}
getImageLink = function() {
	var src = $("#quran-div").attr("src");
	src = src.replace("/embed-local?", "");
	src = src.replace("&title-mean", "");
	src = src.replace("&title", "");
	src = src.replace("&bism", "");
	src = src.replace("&fnclr", "");
	if (!$("#chk-print-friendly-ref").is(":checked")) {
		// default embed has ref=0
		src += "&ref=0";
	}
	src += "&" + buildFontSizeParams();
	if ($("#chk-print-friendly-title").is(":checked")) {
		src += "&title";
	}
	if ($("#chk-print-friendly-title-mean").is(":checked")) {
		src += "&title-mean";
	}
	if ($("#chk-print-friendly-bismillah").is(":checked")) {
		src += "&bism";
	}
	if ($("#chk-print-friendly-expand-footnotes").is(":checked")) {
		src += "&exfn";
	}
	if ($("#chk-print-friendly-colorful-footnotes").is(":checked")) {
		src += "&fnclr";
	}
	src += "&img";
	src = encodeURIComponent(src);
	var surahName = CHAPTER_NAMES_ENGLISH[intVal("#cbo-surah")];
	var pn = $("#chk-print-friendly-page-numbers").is(":checked") ? "1" : "0";
	var params = [];
	params.push("u=" + src);
	params.push("o=" + surahName.replace(" ","-"));
	params.push("s=" + surahName);
	return "http://amrayn.com/quran/img?" + params.join("&");
}

asyncImageLinkShort = function(callback, pngLink) {
	if (pngLink == undefined) {
		pngLink = getImageLink();
	}
	return pngLink;
	// x.co makes call to this image causing it to cache the image so we say no-cache
	// draw back of it is that who ever uses this link it will never be cached (or read from cache)
	//
	// 7/11/14 we are disabling it since it's causing grief to caching
	//pngLink += "&nocache";
	//pngLink = pngLink.substr("http://amrayn.com/".length);
	//return shortenUrl(pngLink, callback);
}

openPDFLink = function() {
	var link = getPDFLink();
	$("#print-friendly-dialog").dialog("close");
	window.open(link);
}

openImageLink = function() {
	var link = getImageLink();
	$("#print-friendly-dialog").dialog("close");
	window.open(link);
}
getMushafLink = function() {
	var selectedVerse = intVal("#verse-selection-select");
	if (selectedVerse == 0) {
		selectedVerse = 1;
	}
	return selectedVerse == 1 ? "/quran/" + intVal("#cbo-surah") :  "/quran/" + intVal("#cbo-surah") + "/" + selectedVerse;
}
openInMushaf = function() {
	window.open(getMushafLink());
}
getTafsirIbnKathirLink = function() {
	var selectedVerse = intVal("#verse-selection-select");
	// We do not change 0 to 1 since 0 has it's own meaning
	return "/quran/tafsiribnkathir/" + intVal("#cbo-surah") + "/" + selectedVerse;
}

getSelectionMp3Link = function() {
	var base = "/quran/mp3";
	var surah = intVal("#cbo-surah");
	var verseStart = intVal("#verse-range-from");
	var verseEnd = intVal("#verse-range-to");
	var reciter = parseInt($("#cbo-reciters option:selected").val()) + 1;
  reciter = RECITERS[$("#cbo-reciters option:selected").val()].id;
	var includeBism = $("#chk-download-sel-bism").is(":checked") && verseStart == 1;
	var ALLOWED_VERSES = 50;
	var totalSelected = (verseEnd - verseStart) + 1;
	if (verseStart > 1 || verseEnd < totalVerses() && verseStart != verseEnd && reciter != undefined && totalSelected <= ALLOWED_VERSES) {
		return base + "?s=" + surah + "&v=" + verseStart + "-" + verseEnd + "&r=" + reciter + (includeBism ? "&bism" : "");
	}
	return "#";
}

updatePartMp3Link = function() {
	var selectionMp3Link = getSelectionMp3Link();
//	$("#link-download-sel-mp3").attr("href", selectionMp3Link); // we use onclick
	if (intVal("#verse-range-from") == 1) {
		$(".chk-download-sel-bism").show();
	} else {
		$(".chk-download-sel-bism").hide();
	}
}

View = {
	defaultView : 2,
	cookieName : "view",
	 // 1 = small-window  -  2 = fixed-menu
	currentView : 2,
	isSmallWindowView : function() {
		return View.currentView == 1;
	},
	isFixedMenuView : function() {
		return View.currentView == 2;
	},
	switchView : function() {
		View.isSmallWindowView() ? View.triggerFixedMenuView() : View.triggerSmallWindowView();
	},
	refresh : function() {
		View.isSmallWindowView() ? View.triggerSmallWindowView() : View.triggerFixedMenuView();
	},
	triggerSmallWindowView : function() {
		View.currentView = 1;
		$("#left-menu").css("position", "");
		$("#quran-div").css("overflow-y", "auto");
		$("#main-contents").css("padding-bottom", "0px");
		$(".body-contents").css("padding-bottom", "6em");
		scrollToSelectedVerseSelector = "#quran-div";
		View.saveCookie();
		$(window).unbind("scroll");
		$("#scroll-to-top").hide();
		scrollToSelectedVerse();
	},
	triggerFixedMenuView : function() {
		View.currentView = 2;
		if ($(window).width() > 799) {
			$("#left-menu").css("position", "fixed");
			// Only pad from bottom on large devices
			$("#main-contents").css("padding-bottom", "6em");
		}
		$("#quran-div").css("overflow-y", "");
		$(".body-contents").css("padding-bottom", "0px");
		scrollToSelectedVerseSelector = "html, body";
		View.saveCookie();
		$(window).scroll(function () {
			if ($(this).scrollTop() >= 100) {
				$("#left-menu").css("top", 0);
			} else {
				$("#left-menu").css("top", "auto");
			}
			// We re-do this as well since other view would unbind the whole event
			if ($(this).scrollTop() > 220) {
				$("#scroll-to-top").fadeIn();
			} else {
				$("#scroll-to-top").fadeOut();
			}
		});
		scrollToSelectedVerse();
	},
	saveCookie : function() {
		saveCookie(View.cookieName, View.currentView);
	},
	readFromCookie : function() {
		View.currentView = parseInt(cook(View.cookieName, View.defaultView));
	}
};
