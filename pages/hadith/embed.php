<?php

// DESCRIPTION

include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
// -------------------------------------- INCLUDES --------------------------------------
includeOnce("pages/embeddable-page.php");
includeOnce("core/models/Hadith.php");
includeOnce("core/queries/HadithQueries.php");
includeOnce("core/utils/UrlUtils.php");
$config = Config::getInstance();
// -------------------------------------- CONTENTS --------------------------------------
$_GET["ignore-pageview"]=true;
$collectionName = UrlUtils::paramValue("collectionName");
$bookNumber = UrlUtils::paramValue("book");
$hadithNumber = UrlUtils::paramValue("hadith");
$collectionId = Hadith::determineCollectionIdFromShortName($collectionName);

$collectionId = $collectionId === null ? 1 : $collectionId;
$bookNumber = $bookNumber === null ? 1 : $bookNumber;
$hadithNumber = $hadithNumber === null ? 1 : $hadithNumber;

$hadith = HadithQueries::queryHadithByRef(1, $collectionId, $bookNumber, $hadithNumber);

$title = "Hadith";
$metaDescription = "Browse and search hadith from complete collections of Sahih Bukhari, Sahih Muslim, Maliks Muwatta, Jami-at Tirmidhi, Sunan an-Nasai, Sunan Ibn Majah, Sunan Abu Dawood, Nawawi 40 Hadiths, Hadith Qudsi, Riyaad us Saliheen and Shamail Muhammadiyah";
$metaKeywords = "";
if ($hadith !== null) {
	$title = $hadith->referenceText("Number");
	$metaDescription = StringUtils::cleanAlphaNumber(str_replace("<br/>", " ", $hadith->text), array("-.! \/<>")) . " $title";
	$metaKeywords = StringUtils::cleanAlphaNumber($hadith->tags, array(",-.! "));
}
$contents = getJSResources(array("/scripts/hadith.js"));
$embeddedJS = <<<JS
    User = {};
    User.Permissions = {};
    User.Preferences = {};
    User.Permissions.canEditHadith = false;
    User.Permissions.canMergeHadith = false;
    User.Preferences.preferArabicHadith = false;
	var IS_EMBEDDED = true;
	var HADITH_VERSION = $config->HADITH_VERSION;
	$(document).ready(function() {
		var collection = "$collectionId";
		var book = "$bookNumber";
		var hadith = "$hadithNumber";
		$("#hadith-collections").val(collection);
		collectionChanged(function() {
		  $("#books:has(option[value=" + book + "])").val(book);
		  bookChanged(function() {
			  $("#hadiths:has(option[value='" + hadith + "'])").val(hadith);
			  	hadithChanged(null, false) // we are not autoLoading this - we are coming from referer
		  })
	  });
	});
JS;

if (isset($_GET["references"])) {
	$embeddedJS .= "var SHOW_REF = true;";
} else {
	$embeddedJS .= "var SHOW_REF = false;";
}
if (isset($_GET["classification"])) {
	$embeddedJS .= "var SHOW_CLASSIFICATION = true;";
} else {
	$embeddedJS .= "var SHOW_CLASSIFICATION = false;";
}
$embeddedCSS="";
if (!isset($_GET["browse"])) {
	$embeddedCSS .= ".left-menu,.only-on-browse{display:none!important;}";
}
$contents .= <<<PAGE
	<script>
	$embeddedJS
	</script>
    <link rel='stylesheet' type='text/css' href='/styles/hadith.css?v=$config->VERSION' />

	<style>
		.link-small-icon {
		  	display:none !important;
		}
		.hadith-container {
			-moz-box-shadow: none;
			-webkit-box-shadow: none;
			box-shadow: none;
			-ms-filter:none;
			filter: none !important;
		}
		$embeddedCSS
	</style>
	<div class='left-menu' id='left-menu'>
		<select name='hadith-collections' id='hadith-collections' onchange="collectionChanged()" class="script-uthmani">
			<option value="1" ref-text="Sahih al-Bukhari">Sahih al-Bukhari - صحيح البخاري</option>
			<option value="2" ref-text="Sahih Muslim">Sahih Muslim - صحيح مسلم</option>
			<option value="4" ref-text="Muwatta Imam Malik">Muwatta Imam Malik - موطأ مالك</option>
			<option value="6" ref-text="Jami` at-Tirmidhi">Jami` at-Tirmidhi - جامع الترمذي</option>
			<option value="7" ref-text="Sunan Ibn Majah">Sunan Ibn Majah - سنن ابن ماجه</option>
			<option value="9" ref-text="Sunan an-Nasa'i">Sunan an-Nasa'i - سنن النسائي</option>
			<option value="3" ref-text="Sunan Abu Dawood">Sunan Abu Dawood - سنن أبي داود</option>
			<option value="5" ref-text="Hadith Qudsi">Hadith Qudsi - الحديث القدسي</option>
			<option value="8" ref-text="An-Nawawi 40 Hadiths">An-Nawawi 40 Hadiths - الأربعون النووية</option>
			<option value="10" ref-text="Riyad Us Saliheen">Riyad Us Saliheen - رياض الصالحين</option>
			<option value="11" ref-text="Shama'il at-Tirmidhi">Shama'il at-Tirmidhi - الشمائل الترمذي</option>
		</select>
		<br/><br/>
		<span id='span-books' style='display:none'>
			<select name='books' id='books' onchange='bookChanged()'>
			</select>
		</span>

		<span id='span-hadiths' style='display:none'>
			<select name='hadiths' id='hadiths' onchange='hadithChanged(null, false)'>
			</select>
		</span>
		<div class='hadith-nav'>
			<br/>
			<button class='prev-hadith fa fa-arrow-left'></button>
			<button class='next-hadith fa fa-arrow-right' style='float: right;'></button>
		</div>
		<div style='clear:both'></div>
	</div>

	<br class='only-on-browse'/>
	<div id="selected-hadith" style="min-height:4em;font-size: 1.3em;line-height: 1.3em;">
		<div id="results-text" style="">
PAGE;
if ($config->PRE_RENDER_HADITH) {
	$hadithEnglish = $hadith;
	$hadithArabic = HadithQueries::queryHadithByRef(2, $collectionId, $bookNumber, $hadithNumber);

	// Hadith text
	$hadithTitle = Hadith::determineLongNameFromCollectionId($collectionId);
	if ($hadithEnglish->hasBooks()) {
		$hadithTitle .= " Book $hadithEnglish->book";
	}
	$hadithTitle .= " Hadith $hadithEnglish->hadith</span>";
	$htmlHadith = <<<H
				<h2 class='hadith-title'>$hadithTitle</h2>
				<div id='content-tabs' class='content-tabs'>
H;
	if ($hadithEnglish !== null) {
		$htmlHadith .= "<div id='tab1'><span class='hadith-text'>$hadithEnglish->text</span></div>";
	}
	if ($hadithArabic !== null) {
		$htmlHadith .= "<div id='tab2'><span class='hadith-text'>$hadithArabic->text</span></div>";
	}
	$htmlHadith .= "</div>";
	$contents .= "<div class='hadith-container'>$htmlHadith</div>";
} else {
	$contents .= "Loading...";
}
$contents .= <<<PAGE
		</div>
	</div>
PAGE;
// ------------------------------------- INITIALIZE ------------------------------------
$metaImages = array();
init($contents, array(
	"title" => $title,
	"meta_description" => $metaDescription,
	"context" => Context::Hadith,
	"meta_images" => $metaImages,
	"meta_keywords" => "$collectionName,$title,$metaKeywords",
	"breadcrumbs" => array("Home" => "/", "Hadith" => "/hadith/", "Collection" => "/hadith/", "Number" => "/hadith/")
));
?>
