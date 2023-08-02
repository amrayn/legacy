<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("pages/page.php");
includeOnce("core/utils/StringUtils.php");
includeOnce("core/utils/Utils.php");

includeOnce("core/queries/AyahQueries.php");
includeOnce("core/queries/SurahInfoQueries.php");
includeOnce("core/queries/QuranMetaQueries.php");
includeOnce("core/queries/VerseByVerseRecitersQueries.php");
$config = Config::getInstance();
$_GET["ignore-pageview"]=true;

$allSurahs = SurahInfoQueries::queryByTypes(array(SurahInfoTypes::SurahName, SurahInfoTypes::SurahNameMeaning, SurahInfoTypes::SurahNameEnglish, SurahInfoTypes::VerseCount));
$ref = Utils::buildQuranRef($_GET["go"], $allSurahs);
$surah = $ref["surah"];
$verseStart = $ref["start"];
$verseEnd = $ref["end"];
$verseParam = "$verseStart-$verseEnd";

$surahInfoResult = SurahInfoQueries::queryBySurah($surah);
$verseCountInfo = SurahInfo::getInfoByType(SurahInfoTypes::VerseCount, $surahInfoResult, true);
$surahEnglishName = SurahInfo::getInfoByType(SurahInfoTypes::SurahNameEnglish, $surahInfoResult, true);

$completeSurah = $verseStart == 1 && $verseEnd == $verseCountInfo;
$translations = explode(",", $config->QURAN_DEFAULT_TRANSLATIONS);
if (isset($_GET["tid"])) {
	$translations = array_merge($translations, explode(",", $_GET["tid"]));
	$translations = array_reverse(array_slice($translations, -4, 4, true));
}
$csvTranslations = implode(",", $translations);

$HIDE_ID = $config->QURAN_HIDE_ID;
$showTranslation = (int)($translations[0] != $HIDE_ID);
$showTranslation2 = (int)($translations[1] != $HIDE_ID);
$showTranslation3 = (int)($translations[2] != $HIDE_ID);
$showTranslation4 = (int)($translations[3] != $HIDE_ID);
$tafsirId = StringUtils::cleanNumber(isset($_GET["tafid"]) ? $_GET["tafid"] : $HIDE_ID);
$transliterationId = StringUtils::cleanNumber(isset($_GET["triid"]) ? $_GET["triid"] : $HIDE_ID);
$showTafsir = (int)($tafsirId != $HIDE_ID);
$showTransliteration = (int)($transliterationId != $HIDE_ID);
$reciter = isset($_GET["rec"]) ? $_GET["rec"] : $config->QURAN_DEFAULT_RECITER;
$showWordByWord = isset($_GET["wrd"]) ? $_GET["wrd"] : "0";
$separators = isset($_COOKIE["read-separator"]) ? $_COOKIE["read-separator"] : "true";
$labels = isset($_COOKIE["read-labels"]) ? $_COOKIE["read-labels"] : "true";
$rukuh = "false";//isset($_COOKIE["read-rukuh"]) ? $_COOKIE["read-rukuh"] : "true";
$readStyle = isset($_GET["sty"]) ? $_GET["sty"] : $config->QURAN_DEFAULT_STYLE;
$quranScriptId = isset($_COOKIE["read-quran-script"]) ? $_COOKIE["read-quran-script"] : $config->QURAN_DEFAULT_SCRIPT;
$scriptFont = isset($_COOKIE["read-quran-font"]) ? $_COOKIE["read-quran-font"] : 11;
$hideScript = isset($_GET["hidescript"]) || $scriptFont == 3;
$initScriptValue = $hideScript ? 3 : $scriptFont;

$totalVerses = $verseCountInfo;
$introAyahs = AyahQueries::queryBySurahStartEndAndDatabase($surah, $verseStart, min($verseStart + 5, $verseEnd), $translations[0]);
$description = "";
foreach ($introAyahs as &$ayah) {
	$description .= "$ayah->ayah. $ayah->text. ";
}
$keywords = "Surah $surahEnglishName,Quran $surah" . ($completeSurah ? "" : ":$verseStart-$verseEnd");
$title = "Surah $surahEnglishName";
if ($verseStart == $verseEnd) {
	$title .= " $surah:$verseStart";
} else if ($verseStart > 1 || $verseEnd < $verseCountInfo) {
	$title .= " $surah:$verseStart-$verseEnd";
}

$widgetParams = "scr=$scriptFont&qscrid=$quranScriptId&tri=$showTransliteration&triid=$transliterationId&tid=$csvTranslations&wrd=$showWordByWord&taf=$showTafsir&tafid=$tafsirId&style=$readStyle&s=$surah&v=$verseParam&fnclr&title&title-mean";
if ($surah != 1 && $surah != 9) {
	$widgetParams .= "&bism";
}
if ($hideScript) {
	$widgetParams .= "&hidescript";
}
if ($separators == "false") {
	$widgetParams .= "&nosep";
}
if ($labels == "false") {
	$widgetParams .= "&nolabels";
}
if ($rukuh == "false") {
	$widgetParams .= "&norukuh";
}
$baseWidget = "$config->DOMAIN_SECURE/quran/embed-local?_=$config->QURAN_VERSION&";
$embeddedSourceUrl = "$baseWidget$widgetParams";
$embeddedSourceContents = UrlUtils::getSslPage("$embeddedSourceUrl");
$showWordByWordChecked = $showWordByWord ? "checked" : "";
$_GET["sharing-quran"] = "true";
$socialMediaShareButtons = includeContents("pages/embedded/contents/social-media-share.php");
$embeddedCSS .= <<<CSS
	#scroll-to-top {
		bottom: 8em;
	}
	.floating-nav {
		display:none;
		bottom: 7.3em;
		position: fixed;
		padding: 0.5em 1em 0.25em 1em;
		font-size: 1.1em;
		background-color: #000;
		color: #fff !important;
		font-family: UthmaniScript,Arial,Helvetica,sans-serif;
		border-radius: 3px;
		-moz-border-radius: 3px;
		-webkit-border-radius: 3px;
		font-weight: bold;
		opacity: 0.5;
		text-decoration: none;
		z-index: 999999;
	}
	.floating-nav:hover {
	  color: #999 !important;
	  opacity: 1;
	}
	#next-ayah-float {
		left: 4em;
	}
	#prev-ayah-float {

	}
	button {
  	  border-style: none;
	}
	button.toolbtn {
		top: -5px;
		position: relative;
	}
	button.toolbtn:hover {
	  border: 0px;
	  background-color: transparent !important;
  	}
	button.toolbtn > span:hover {
	  color: #fff;
  	}
	#foot-contents {
		display: none !important;
	}
	section.top-menu {
	    width: 98%;
	    padding: 5px;
		transition: all .4s ease-in-out;
	}
	.top-menu-section {
	    border-right: 1px solid rgba(51, 51, 51, 0.40);
	    display: inline-block;
		height: 100%;
	}
	section.bottom-menu {
		position: fixed;
		bottom: 0;
		left:0px;
		width:100%;
		height: 80px;
		opacity: 0.97;
	}
	.fixed-top-menu {
		left: 0px;
	    position: fixed;
		top: 75px;
		width:100% !important;
		background: #fff;
		-webkit-box-shadow: 0px 3px 20px 0px rgba(50, 50, 50, 0.9);
		-moz-box-shadow:    0px 3px 20px 0px rgba(50, 50, 50, 0.9);
		box-shadow:         0px 3px 20px 0px rgba(50, 50, 50, 0.9);
	    border-top: 0px !important;
		border-bottom: 0px !important;
	}

@media(min-width: 374px) {
	.top-menu-opener {
		top: 59px !important;
	}
	.fixed-top-menu {
		top: 56px !important;
	}
}

@media(min-width: 705px) {
	.top-menu-opener {
		top: 39px !important;
	}
	.fixed-top-menu {
		top: 32px !important;
	}
}
	.fixed-top-menu-quran-div {
	    padding-top: 100px;
	}
	.bottom-menu-section {
		display: inline-block;
	}
	.menu-item-label {
		font-size: 1.2em;
		margin-right: 0.2em;
	}
	#btn-play {
	  -webkit-border-radius: 5px;
	  -moz-border-radius: 5px;
	  border-radius: 50%;
	  -ms-filter: "progid:DXImageTransform.Microsoft.Alpha(Opacity=0)";
	  filter: alpha(opacity=0);
	  top: -12px;
	  position: relative;
	  height: 4em;
	  width: 4em;
	}
	#btn-play.play-grey {
	  background-color: grey !important;
	}
	#btn-play>span {
  	  color: #fff;
	}
	#btn-play:hover {
	  background: #000 !important;
	}
	#seek-bar {
	    width: 100%;
	    left: 0px;
		top:-10px;
	}
	#seek-bar > a.ui-slider-handle, #verse-start-end-pos > div.ui-slider-range {
	    border: 1px solid #D3D3D3;
	}

	.bottom-menu-section.recitation-bar {
		left: 35%;
		position: relative;
		width: 250px;
	}
	@media (max-width:670px) {
		.bottom-menu-section.recitation-bar {
			left: 20%;
			width: 200px;
		}
	}
	@media (max-width:400px) {
		.bottom-menu-section.recitation-bar {
			left: 5%;
			width: 225px;
		}
	}
	#verse-start-end-pos {
	    width: 100%;
	    display: block;
	    height: 0.5em;
	    top: -18px;
	}
	#verse-start-end-pos-val {
		text-align: center;
  	 	position: relative;
  	  	z-index: 10;
  	  	top: -17px;
  	  	background-color: rgba(0,0,0,0.8);
  	  	color: #fff;
	}
	.top-menu-section-as-menu {
		display: block !important;
  		background: #FFF;
  		padding: 1em;
  	  	width: 90%;
		position: absolute;
		height: auto;
		left : 1%;
		border: 0px;
		max-width: 650px;
	}
	@media (min-width: 100px) {
		.top-menu-section.links-menu {
			display: none;
		}
		.top-menu-section-icon.links-menu {
			display: inline-block !important;
		}
	}
	@media (min-width: 100px) {
		.top-menu-section.settings-menu {
			display: none;
		}
		.top-menu-section-icon.settings-menu {
			display: inline-block !important;
		}
	}
	@media (min-width: 100px) {
		.top-menu-section.reciter-menu {
			display: none;
		}
		.top-menu-section-icon.reciter-menu {
			display: inline-block !important;
		}
	}
	@media (min-width: 100px) {
		.top-menu-section.translation-menu {
			display: none;
		}
		.top-menu-section-icon.translation-menu {
			display: inline-block !important;
		}
	}
	@media (max-width: 650px) {
		.top-menu-section.browse-menu {
			display: none;
		}
		.top-menu-section-icon.browse-menu {
			display: inline-block !important;
		}
		.top-menu {
			text-align: center;
		}
		.top-menu>.top-menu-section {
			text-align: left;
		}
	}
	.top-menu-section-icon {
		font-size: 2em;
		border-radius: 50%;
		text-align: center;
		padding: 0.4em;
		display: none;
		color: #fff;
		width: 36px;
		cursor: default;
	}
	.top-menu-section-icon.browse-menu {
		background-color: rgb(197, 168, 76) !important;
	}
	.top-menu-section-icon.browse-menu.top-menu-section-icon-selected {
		border-radius: 0px;
	}
	.top-menu-section.browse-menu.top-menu-section-as-menu {
		border: 1px solid rgb(197, 168, 76) !important;
  		border-top: 3px solid rgb(197, 168, 76) !important;
		-moz-box-shadow: 0px 0px 4px 0px rgb(197, 168, 76);
	 	-webkit-box-shadow: 0px 0px 4px 0px rgb(197, 168, 76);
		box-shadow: 0px 0px 4px 0px rgb(197, 168, 76);
		background: #F0EAD7;
	}
	.top-menu-section-icon.translation-menu {
		background-color: rgb(0, 210, 158) !important;
	}
	.top-menu-section-icon.translation-menu.top-menu-section-icon-selected {
		border-radius: 0px;
	}
	.top-menu-section.translation-menu.top-menu-section-as-menu {
		border: 1px solid rgb(0, 210, 158) !important;
  		border-top: 3px solid rgb(0, 210, 158) !important;
		-moz-box-shadow: 0px 0px 4px 0px rgb(0, 210, 158);
	 	-webkit-box-shadow: 0px 0px 4px 0px rgb(0, 210, 158);
		box-shadow: 0px 0px 4px 0px rgb(0, 210, 158);
		background: #F0FFF0;
	}
	.top-menu-section-icon.reciter-menu {
		background-color: rgb(41, 126, 126) !important;
	}
	.top-menu-section-icon.reciter-menu.top-menu-section-icon-selected {
		border-radius: 0px;
	}
	.top-menu-section.reciter-menu.top-menu-section-as-menu {
		border: 1px solid rgb(41, 126, 126) !important;
  		border-top: 3px solid rgb(41, 126, 126) !important;
		-moz-box-shadow: 0px 0px 4px 0px rgb(41, 126, 126);
	 	-webkit-box-shadow: 0px 0px 4px 0px rgb(41, 126, 126);
		box-shadow: 0px 0px 4px 0px rgb(41, 126, 126);
		background: #D3E2DF;
	}
	.top-menu-section-icon.settings-menu {
		background-color: rgb(223, 50, 39) !important;
	}
	.top-menu-section-icon.settings-menu.top-menu-section-icon-selected {
		border-radius: 0px;
	}
	.top-menu-section.settings-menu.top-menu-section-as-menu {
		border: 1px solid rgb(223, 50, 39) !important;
  		border-top: 3px solid rgb(223, 50, 39) !important;
		-moz-box-shadow: 0px 0px 4px 0px rgb(223, 50, 39);
	 	-webkit-box-shadow: 0px 0px 4px 0px rgb(223, 50, 39);
		box-shadow: 0px 0px 4px 0px rgb(223, 50, 39);
		background: #FAD8D8;
	}
	.top-menu-section-icon.links-menu {
		background-color: rgb(54, 189, 241) !important;
	}
	.top-menu-section-icon.links-menu.top-menu-section-icon-selected {
		border-radius: 0px;
	}
	.top-menu-section.links-menu.top-menu-section-as-menu {
		border: 1px solid rgb(54, 189, 241) !important;
  		border-top: 3px solid rgb(54, 189, 241) !important;
		-moz-box-shadow: 0px 0px 4px 0px rgb(54, 189, 241);
	 	-webkit-box-shadow: 0px 0px 4px 0px rgb(54, 189, 241);
		box-shadow: 0px 0px 4px 0px rgb(54, 189, 241);
		background: #F0FEFF;
	}
	.top-menu-opener {
		display:none;
		background-color: #000;
		color: #fff;
		top: 84px;
		position: fixed;
		left: 5px;
		opacity: .50;
		padding: 1em;
		cursor:pointer;
	}
	.top-menu-opener:hover {
		opacity:.8;
	}
CSS;

function mapTrans($trans) {


	return json_encode(array_map(function($item) {
		$s = new stdClass();
		$s->id = $item->id;
		$s->name = $item->langName . ($item->langName === $item->name ? "" : " - " . $item->name);
		return $s;
	}, $trans), JSON_UNESCAPED_UNICODE);
}

function mapReciters($r) {

	return json_encode(array_map(function($item) {
		
		$s = new stdClass();
		$s->id = $item->id;
		$s->name = $item->name;
		$s->urlPattern = $item->urlPattern;
		$s->basmalaUrl = $item->basmalaUrl;
		return $s;
	}, $r), JSON_UNESCAPED_UNICODE);
}


$translationsMeta = QuranMetaQueries::queryByType(QuranMetaTypes::Translation);
$embeddedJS = "var TRANSLATIONS = " . mapTrans($translationsMeta) . ";\n";

$transliterationsMeta = QuranMetaQueries::queryByType(QuranMetaTypes::Transliteration);
$embeddedJS .= "var TRANSLITERATIONS = " . mapTrans($transliterationsMeta) . ";\n";

$tafsirsMeta = QuranMetaQueries::queryByType(QuranMetaTypes::Tafsir);
$embeddedJS .= "var TAFSIRS = " . mapTrans($tafsirsMeta) . ";\n";

$recitersData = VerseByVerseRecitersQueries::queryAllActive();
$embeddedJS .= "var RECITERS = " . mapReciters($recitersData) . ";\n";
$embeddedJS .= "var CHAPTER_NAMES_ENGLISH = [-1,";
for ($i = 1; $i <= 114; ++$i) {
	$embeddedJS .= "\"" . SurahInfo::getInfoByTypeAndSurah(SurahInfoTypes::SurahNameEnglish, $i, $allSurahs, true) . "\"";
	if ($i < 114) {
		$embeddedJS .= ",";
	}
}
$embeddedJS .= "];\n";
$embeddedJS .= "var VERSE_COUNT = [-1,";
for ($i = 1; $i <= 114; ++$i) {
	$embeddedJS .= SurahInfo::getInfoByTypeAndSurah(SurahInfoTypes::VerseCount, $i, $allSurahs, true);
	if ($i < 114) {
		$embeddedJS .= ",";
	}
}
$embeddedJS .= "];\n";
$embeddedJS .= <<<JS
	var DEFAULT_TRANSLATIONS = [$config->QURAN_DEFAULT_TRANSLATIONS];
	var _HIDE_ID = $HIDE_ID;
	var DEFAULT_TRANSLITERATION = _HIDE_ID;
	var DEFAULT_TAFSIR = _HIDE_ID;
	var DEFAULT_ARABIC_FONT = $config->QURAN_DEFAULT_ARABIC_FONT;
	var DEFAULT_RECITER = $config->QURAN_DEFAULT_RECITER;
	var initialScriptValue = "$initScriptValue";
	var readStyle = "$readStyle";
	var initialQuranScriptId = "$quranScriptId";
	var baseWidget = "$baseWidget";
	var SHOW_TRANSLATIONS = [$showTranslation, $showTranslation2, $showTranslation3, $showTranslation4];
	var TRANSLATION_IDS = [$csvTranslations];
	var transliterationID = "$transliterationId";
	var _showTransliteration = $showTransliteration;
	var _showTafsir = $showTafsir;
	var tafsirID = "$tafsirId";
	var _reciterID = "$reciter";
	var _surah = $surah;
	var _verseStart = $verseStart;
	var _verseEnd = $verseEnd;
	var _selectedVerse = parseInt(location.hash == "" || location.hash == "#" ? (_verseStart == 1 ? 0 : _verseStart) : location.hash.substr(1));
        var STATIC_RESOURCES_BASE_RECITATIONS = "$config->STATIC_RESOURCES_BASE/verse-by-verse";
	if (isNaN(_selectedVerse)) {
		_selectedVerse = _verseStart == 1 ? 0 : _verseStart;
	} else {
		if (_selectedVerse != 0 && _selectedVerse < _verseStart) {
			_selectedVerse = Math.max(_selectedVerse, _verseStart);
		} else if (_selectedVerse != 0 && _selectedVerse > _verseEnd) {
			_selectedVerse = Math.min(_selectedVerse, _verseEnd);
		}
	}
	closeAllTooltips = function() {}
	showAppropriateMenu = function() {
		var readingMode = $("#chk-reading-mode").is(":checked");
		var isFixedInStart = !$("section.top-menu").hasClass("fixed-top-menu");
	    if ($(window).scrollTop() > 100){
	        $("section.top-menu").addClass("fixed-top-menu");
	        $("#quran-div").addClass("fixed-top-menu-quran-div ");
			if (readingMode) {
	        	$("section.top-menu.fixed-top-menu").css("display", "none");
	        	$("section.top-menu-opener").css("display", "block");
				$('body, #body-contents').click();
			} else {
	        	$("section.top-menu.fixed-top-menu").css("display", "block");
	        	$("section.top-menu-opener").css("display", "none");
				if (isFixedInStart) {
					$(".top-menu-section-as-menu").removeClass("top-menu-section-as-menu");
					$(".top-menu-section-icon").removeClass("top-menu-section-icon-selected");
				}

			}
	    } else {
	        $("section.top-menu").removeClass("fixed-top-menu");
	        $("#quran-div").removeClass("fixed-top-menu-quran-div ");
	        $("section.top-menu-opener").css("display", "none");
			if (readingMode) {
	        	$("section.top-menu").css("display", "block");
				$('body, #body-contents').click();
			} else {
				if (!isFixedInStart) {
					$(".top-menu-section-as-menu").removeClass("top-menu-section-as-menu");
					$(".top-menu-section-icon").removeClass("top-menu-section-icon-selected");
				}
			}
	    }
	}
	onloads[onloads.length++] = function() {

		initializeControls();
		initializeFunctions();
		$("#quran-div").attr("src", "$embeddedSourceUrl");
		quranFrameLoaded();
		$(window).scroll(function(){
			showAppropriateMenu();
		});
		$("section.top-menu-opener").click(function() {
			//$("#chk-reading-mode").prop('checked', false);
			//$("#chk-reading-mode").change();
			$("section.top-menu.fixed-top-menu").show();
		});
		$(".top-menu-section-icon").mouseenter(function(){
			if ($(".top-menu-section-as-menu").length > 0) {
				$(this).click();
			}
		});
		$(".top-menu-section-icon").click(function(){
			$(".top-menu-section-icon").removeClass("top-menu-section-icon-selected");
			$(".top-menu-section-as-menu").removeClass("top-menu-section-as-menu");
			var menuClass = $(this).attr("class").split(" ")[1];
			var menu = $(".top-menu-section-icon." + menuClass).parent().find(".top-menu-section." + menuClass);
			var newLeft = $(this).offset().left;
			if (menu.width() + newLeft >= $(window).width()) {
				newLeft = $(window).width() - menu.width();
			}
			var newTop = $(this).height() + $(this).offset().top + 25;
			menu.addClass("top-menu-section-as-menu");
			if ($(window).scrollTop() > 100){
				menu.css("top", $(".top-menu-section-icon.browse-menu").is(":visible") ? 60 : 115);
			} else {
				menu.css("top", newTop);
			}
			$(this).addClass("top-menu-section-icon-selected");
		});

		$(window).resize(function() {
			if (ScreenSize.get() >= 950) {
				$(".top-menu-section-as-menu").removeClass("top-menu-section-as-menu");
				$(".top-menu-section-icon").removeClass("top-menu-section-icon-selected");
			}
		});

		$('body, #body-contents').click(function(e) {
			var targetIsLink = $(e.target).hasClass('top-menu-section-icon');
			var targetIsInMenu = $('.top-menu-section-as-menu').hasUsingIs($(e.target)) == true || $('.top-menu-section-as-menu').hasUsingIs($(e.target)).length >= 0;

			var targetToOpenMenu = targetIsLink || targetIsInMenu;
			if (!targetToOpenMenu) {
				$('.top-menu-section-as-menu').removeClass('top-menu-section-as-menu')
				$(".top-menu-section-icon").removeClass("top-menu-section-icon-selected");
			}
		});
	};
JS;
$jsResources = getJSResources(array(
	"/scripts/shortcut.js",
	"/scripts/recitation.js",
	"/scripts/quran.js"
));
$jsResourcesNonCompressed = getJSResources(array(
	"/scripts/chosen.jquery.min.js",
	//"/scripts/select2.js",
	"/scripts/sm2-min.js"
), true);
$cssResourcesNonCompressed = getCSSResources(array(
	"/styles/chosen.min.css",
	//"/styles/select2.css"
), true);
$contents = <<<PAGE
	<script>
		$embeddedJS
	</script>
	$cssResourcesNonCompressed
	<style type='text/css'>
		$embeddedCSS
	</style>
	$jsResourcesNonCompressed
	$jsResources
<a href='#' id='prev-ayah-float' notooltip class='floating-nav' title='Previous Ayah (SHIFT+LEFT ARROW)'><span class='fa fa-arrow-left fa-white'></span></a>
<a href='#' id='next-ayah-float' notooltip class='floating-nav' title='Next Ayah (SHIFT+RIGHT ARROW)'><span class='fa fa-arrow-right fa-white'></span></a>

<section class="top-menu-opener fa fa-bars"></section>
<section class='top-menu'>
<section class='top-menu-section-icon browse-menu fa fa-bookmark' notooltip title='Browse / Navigate'>
</section>
	<section class='top-menu-section browse-menu'>
	<label class='menu-item-label'>Chapter</label><select name="cbo-surah" id="cbo-surah" onchange="surahChanged()" class="script-uthmani quran-control-2" style="display:inline; width: 227px;">
PAGE;
		for ($i = 1; $i <= 114; ++$i) {
			$contents .= "<option value='$i'>$i. " . SurahInfo::getInfoByTypeAndSurah(SurahInfoTypes::SurahNameEnglish, $i, $allSurahs, true) . " - " . SurahInfo::getInfoByTypeAndSurah(SurahInfoTypes::SurahName, $i, $allSurahs, true) . " - " . SurahInfo::getInfoByTypeAndSurah(SurahInfoTypes::SurahNameMeaning, $i, $allSurahs, true) . "</option>";
		}
$contents .= <<<PAGE
	</select>&nbsp;&nbsp;<a class='fa fa-book local' id='emushaf-link' notooltip title='Open in E-Mushaf' style='text-decoration:none;color:#444' href='/quran/$surah'></a>
	<a class='fa fa-info-circle local' id='intro-link' style='text-decoration:none;color:#444' notooltip title='Introduction' href='/quran/intro/$surah'></a>
<br/><br/>
<label class='menu-item-label'>From</label><select id="verse-range-from" name="verse-range-from" class="quran-control" style="left: 0px;width:57px;position: relative;"></select> <label class='menu-item-label' style="left: 0px;position: relative;">To</label><select id="verse-range-to" name="verse-range-to" class="quran-control" style="left: 0px;width:57px;position: relative;"></select> |
<label for="verse-selection-label" class='menu-item-label'>Select </label>
<select id="verse-selection-select" name="verse-selection-select" class="quran-control" title="Select an ayah (SHIFT+RIGHT / SHIFT+LEFT) OR (SHIFT+UP / SHIFT+DOWN)" style="left: 0px;width:57px;position: relative;"></select>
	</section>
	<section class='top-menu-section-icon translation-menu fa fa-language' notooltip title='Translation, Tafsir & Word by word meaning'>

	</section>
	<section class='top-menu-section translation-menu'>
	<label class='menu-item-label'>Translation</label><select name="cbo-translations" class="" id="cbo-translations" data-enablecheckbox="true" data-placeholder="Choose a translation..." class="quran-control" style="width:100%" onchange="translationChanged(this)">
		            </select><br/><br/>
<label class='menu-item-label'>Tafsir</label><select name="cbo-tafsir" id="cbo-tafsir" class="script-uthmani" onchange="tafsirChanged(this)" style="width:100%;font-weight:normal;">
		            </select>
<br/><br/>

<label class='menu-item-label'>Transliteration</label><select name="cbo-transliteration" class="" class="quran-control" id="cbo-transliteration"  style="width:100%" onchange="transliterationChanged(this)">
		            </select><br/><br/>
<input type="checkbox"  $showWordByWordChecked  class="quran-control" name="chk-word-by-word" id="chk-word-by-word" onchange="triggerWordByWord(this)"><label for="chk-word-by-word" class='menu-item-label' style="left: 2px;position: relative;">Word by word</label>
	</section>
	<section class='top-menu-section-icon reciter-menu fa fa-volume-up' notooltip title='Recitation'>

	</section>
	<section class='top-menu-section reciter-menu'>
		<div id="cbo-reciters-bar" class="quran-control" style="width:100%">
			<label for="cbo-reciters">Reciter: </label>
			<select name="cbo-reciters" id="cbo-reciters" onchange="reciterChanged()" class="quran-control-2" style="width:75%;">
			</select>
		</div>
		<br/><br/>
		<span id="range-repeat" name="range-repeat" class="memorization-only" >
			<label for="range-repeat-input" style="display:block">Recite all: </label>
			<input id="range-repeat-input" name="range-repeat-input" class="verse-spin-box" value="1" />&nbsp;times
			<input type="hidden" id="range-repeat-internal" value="1" />
		</span>
		<span class="separator memorization-only"></span>
		<span id="range-gap" name="range-gap" class="memorization-only">
			<label for="range-gap-input">Wait: </label>
			<input id="range-gap-input" name="range-gap-input" class="verse-spin-box" value="0" />&nbsp;sec
		</span>
		<br class="memorization-only" /><br class="memorization-only" />
		<span id="verse-repeat" name="verse-repeat" class="memorization-only" >
			<label for="verse-repeat-input" style="display:block">Recite ayah: </label>
			<input id="verse-repeat-input" name="verse-repeat-input" class="verse-spin-box" value="1" />&nbsp;times
			<input type="hidden" id="verse-repeat-internal" value="1" />
		</span>
		<span class="separator memorization-only" ></span>
		<span id="verse-gap" name="verse-gap" class="memorization-only" >
			<label for="verse-gap-input">Wait: </label>
			<input id="verse-gap-input" name="verse-gap-input" class="verse-spin-box" value="0" />&nbsp;sec
			<div style="padding-left: 7em;">
				<input type="checkbox" id="chk-wait-duration" name="chk-wait-duration" /><label for="chk-wait-duration" title="This will add wait length of previous recitation. If you have selected ayah to be recited from middle (by using sliders) this will only wait for seconds ayah was recited for. For example, if you play ayah from 8-12 seconds, wait time will be 4 seconds as this gives you enough time to repeat.">+ Recite length</label>
			</div>
		</span>

		<br class="memorization-only" />
		<input type="checkbox" name="chk-continuous" onchange="continuousChanged(this)" id="chk-continuous"><label for="chk-continuous" title="Checking this will enable moving to next ayah (this option honours repetition options).">Continuous</label>
		<br/>
		<input type="checkbox" name="chk-mem-mode" onchange="memorizationModeChanged(this)" id="chk-mem-mode"><label for="chk-mem-mode">Memorization Mode</label>
		<br/>
		<input type="checkbox" checked name="chk-hide-recitation-bar" id="chk-hide-recitation-bar" onchange="hideRecitationBarChanged(this)" /><label for="chk-hide-recitation-bar">Hide Recitation Bar</label>
		<!--<br /> <a href='#' onclick='generateMp3Range()' rel='nofollow' id='link-download-sel-mp3' style='text-decoration:none' class='transition'>Download Selected Range</a>
                <input type="checkbox" name="chk-download-sel-bism" id="chk-download-sel-bism" class="chk-download-sel-bism" /> <label for="chk-download-sel-bism" class="chk-download-sel-bism">Include Bismillah</label>-->
		<br class="memorization-only" /> <a href='/memorization' style='text-decoration:none' class='memorization-only transition'>Open Memorization Tracker</a>

		<br/><br/>
		<div style="
			background: #cc4d07;
			color: #fff;
			position: absolute;
			bottom: 0;
			left: 0;
			right: 0;
			text-align: center;
		">
			⚠ Recitation bar will be removed soon. <a href="/quran/" class="new-exp-link-a" style="color:inherit;font:inherit">Try new experience</a> or <a href="https://github.com/amrayn/planner/issues" style="color:inherit;font:inherit">send feedback</a>.
		</div>
	</section>

	<section class='top-menu-section-icon settings-menu fa fa-gears' notooltip title='Settings (Reading style, font and script etc)'>

	</section>
	<section class='top-menu-section settings-menu'>
    <div id="style-bar" class="quran-control" style="width:45%">
        <select name="style-bar-select" id="style-bar-select" onchange="styleChanged()" class="icon-menu quran-control" style="width:100%">
			<option value="122">- Reading Style -</option> <!-- Turns into 2 -->
            <option value="0">Continuous</option>
            <option value="1">Side by side</option>
            <option value="2" selected>Ayah by ayah</option>
        </select>
    </div>
    <div id="script-style-bar" class="quran-control" style="width:45%">
        <select name="script-font-bar-select" id="script-font-bar-select" class="quran-control" onchange="scriptStyleChanged()" style="width:100%">
            <option value="3" class-style="">- Script Font : Hide -</option> <!-- Default is hiding -->
            <option value="7" class-style="script-lateef">Lateef</option>
            <option value="5" class-style="script-me-quran">Medina Mushaf</option>
            <option value="9" class-style="script-mry">Polished Mushaf Style</option>
            <option value="1" class-style="script-uthmani">Uthmani</option>
            <option value="6" class-style="script-islamicfont">Islamic Font</option>
            <option value="2" class-style="script-indopak">Indo-Pak</option>
            <option value="8" class-style="script-scheherazade" >Scheherazade</option>
            <option value="4" class-style="script-amiri">Amiri</option>
            <option value="10" class-style="script-qalam">Al Qalam</option>
            <option value="11" class-style="script-kingfahd" selected>King Fahd Printing Press</option>
        </select>
    </div>
    <br/><br/>
    <div id="quran-script-bar" class="quran-control" style="width:91%">
        <select name="quran-script-bar-select" id="quran-script-bar-select" class="quran-control" onchange="quranScriptStyleChanged()" style="width:100%">
            <option value="0">- Quran Script -</option> <!-- Turns into 1 -->
            <option value="1" selected>Uthmani</option>
			<option value="6">Uthmani Minimal</option>
            <option value="3">Simple</option>
            <option value="2">Simple Minimal</option>
            <option value="5">Simple Enhanced</option>
            <option value="4">No Tashkeel</option>
        </select>
    </div>
    <br/><br/>
    <input type="checkbox" class="quran-control" name="chk-separator" id="chk-separator" onchange="triggerSeparator(this)"><label for="chk-separator">Separators</label>
    <input type="checkbox" class="quran-control" name="chk-labels" id="chk-labels" onchange="triggerLabels(this)"><label for="chk-labels">Labels</label>
    <!--<input type="checkbox" class="quran-control" name="chk-rukuh" id="chk-rukuh" onchange="triggerRukuh(this)"><label for="chk-rukuh">Rukuh</label>-->
	<br/><br/>
	<input type="checkbox" name="chk-verse-clickable" id="chk-verse-clickable" onchange="updateVerseClickable()" /><label for="chk-verse-clickable" title="Makes ayah clickable to select ayah by clicking on it (or any translation)">Clickable verse</label><br/>
		<input type="checkbox" checked name="chk-selected-verse-scroll" id="chk-selected-verse-scroll" /><label for="chk-selected-verse-scroll" title="When ayah is selected, this option fits ayah in to the view so user is always looking at ayah.">Scroll to verse on click</label>
		<br/>
		<input type="checkbox" checked name="chk-reading-mode" id="chk-reading-mode" onchange="readingModeChanged(this)"  /><label for="chk-reading-mode" title="Hides top bar">Reading Mode</label>
		<br/>
		<input type="checkbox" checked name="chk-floating-nav-btn" id="chk-floating-nav-btn" onchange="floatingNavigationChanged(this)"  /><label for="chk-floating-nav-btn" title="Hide floating navigation buttons">Hide Navigation Buttons</label>
	<hr/>
	<label for="refresh-delay">Refresh Delay: </label>
	<input id="refresh-delay" name="refresh-delay" value="0" class="spin-box quran-control"/> sec
	<div class="icon-bar" style="text-align:left;margin-top:1em;">
		<button onclick="javascript:refreshFrame(undefined, undefined, false, true);" title="Refresh now" id="refresh-now-button" style="font-size: 14px;width: 100%;font-weight: bold;text-align: left;color:#000;" class="toolbtn"><span style="padding-right: 0.5em;color:#000;" class="fa fa-refresh"></span>Refresh Now</button>
	</div>
	</section>
	
</section>
<div class='quran-page' style='display:inline'>
	<div id="quran-div" style='width: 99%;'>
		$embeddedSourceContents
	</div>
</div>
<section class='bottom-menu'>
<section class='bottom-menu-section'>
<div id="zoom-bar" class="quran-control">
    <button onclick="javascript:increaseFont();" id="zoom-bar-zoom-in" title="Zoom in" class="toolbtn" class="toolbar-button"><span class="fa fa-search-plus"></span></button>
    <button onclick="javascript:decreaseFont();" id="zoom-bar-zoom-out" title="Zoom out" class="toolbtn" class="toolbar-button"><span class="fa fa-search-minus"></span></button>
</div>
</section>
<section class='bottom-menu-section recitation-bar'>
<div style='text-align:center'>
	<button name="btn-restart-all" title="Restart from beginning" id="btn-restart-all" class="toolbtn restart-all" disabled="true" onclick="restartAll()"><span class="fa fa-fast-backward"></span></button>
	<button name="btn-restart" title="Restart current aya (SHIFT+O)" id="btn-restart" class="toolbtn restart" disabled="true" onclick="restart()"><span class="fa fa-step-backward"></span></button>
	<button name="btn-play" title="Recite / pause selected aya (SHIFT+P)" id="btn-play" class="toolbtn play" disabled="true" onclick="startReciting()"><span class="fa fa-play"></span></button>
	<button name="btn-stop" title="Stop recitation (SHIFT+S)" id="btn-stop" class="toolbtn stop" disabled="true" onclick="stop()"><span class="fa fa-stop"></span></button>
</div>
<div id="slider-controls">
<div id="verse-start-end-pos-val" class="memorization-only">0-0</div><div id="status-bar" class="quran-control" style="display:none;position:absolute;bottom: -9px;
  display: inline-block;
  text-align: center;
  width: 100%;">Waiting...</div>
<div id="verse-start-end-pos" class="memorization-only" style="width:100%;display:block;height: 0.5em;top:-18px;"></div>
<div id="seek-bar"></div>
</div>
</section>

</section>

<div class="new-exp-link">
	⚠ You are looking at the legacy Quran experience. Check out <a href="/quran/" class="new-exp-link-a" style="color:inherit;font:inherit">new Quran experience</a> with great new features.
</div>
PAGE;

// -------------------------------------------------------------------------------------
init($contents, array(
	"title" => $title,
	"context" => Context::Quran,
	"meta_description" => substr($description, 0, 300),
	"meta_keywords" => $keywords,
	"breadcrumbs" => array("Home" => "/", "Quran" => "/", "Surah" => "/")
));
?>
