<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("core/utils/StringUtils.php");
includeOnce("core/queries/AyahQueries.php");
includeOnce("core/queries/QuranWordQueries.php");
includeOnce("core/queries/SurahInfoQueries.php");
includeOnce("core/queries/QuranMetaQueries.php");

includeOnce("pages/quran/special-chars.php");

// Following is already part of .htaccess so we do not need it here
//header("Access-Control-Allow-Origin: *");
header('Cache-Control: public,max-age=2592000');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 2592000) . ' GMT');
header('Pragma: public');

$config = Config::getInstance();
//--------------------------------------------- PARAMETERS INTERPRETATION ---------------------------------------------
$embedLogo = "<div class='embedded-foot'>Powered by <a href='$config->DOMAIN_SECURE' target='_parent'><img src='https://amrayn.com/assets/images/logo-embed.png?$config->IMG_VERSION' style='width: 74px;position: relative;top: 7px;vertical-align:super;'></a></div>";
$type = $_GET["type"];
$isPdfPrint = $type == "pdf";
$isImageRender = $type == "img";
$isPdfPrintOrImageRender = $isPdfPrint || $isImageRender;
$isPdfPrintOrImageRenderJsBool = $isPdfPrintOrImageRender ? "true" : "false";
$includeMeta = $type != "local";
$contents = "";

$surah = StringUtils::cleanNumber(isset($_GET["s"]) ? $_GET["s"] : 1);
if ($surah < 1 || $surah > 114 || !is_numeric($surah)) {
	$surah = 1;
}
$GLOBALS["surah"] = $surah;

$surahInfoResult = SurahInfoQueries::queryBySurah($surah);
$surahNameArabicInfo = SurahInfo::getInfoByType(SurahInfoTypes::SurahName, $surahInfoResult, true);
$surahNameMeaningInfo = SurahInfo::getInfoByType(SurahInfoTypes::SurahNameMeaning, $surahInfoResult, true);
$totalAyahs = SurahInfo::getInfoByType(SurahInfoTypes::VerseCount, $surahInfoResult, true);
$surahEnglishName = SurahInfo::getInfoByType(SurahInfoTypes::SurahNameEnglish, $surahInfoResult, true);
$surahEnglishPronName = SurahInfo::getInfoByType(SurahInfoTypes::SurahNameEnglishPronounced, $surahInfoResult, true);
$rukuhInfo = SurahInfo::getInfoByType(SurahInfoTypes::SurahRukuhList, $surahInfoResult);
$rukuhInfoArray = $rukuhInfo->asArray();
$GLOBALS["rukuhInfoArray"] = $rukuhInfoArray;

$ayahParam = explode("-", isset($_GET["v"]) ? $_GET["v"] : "1-$totalAyahs");
$ayahStart = 1;
$ayahEnd = $totalAyahs;
if (count($ayahParam) > 1 && is_numeric($ayahParam[0]) && is_numeric($ayahParam[1])) {
	$ayahStart = $ayahParam[0];
	$ayahEnd = $ayahParam[1];
} else if (count($ayahParam) > 0 && is_numeric($ayahParam[0])) {
	$ayahStart = $ayahParam[0];
	$ayahEnd = $ayahStart;
}
$ayahStart = max(min($ayahStart, $totalAyahs), 1);
$ayahEnd = min($ayahEnd, $totalAyahs);
$scriptDbId = isset($_GET["qscrid"]) ? $_GET["qscrid"] : 1;
if (!is_numeric($scriptDbId) || $scriptDbId < 1 || $scriptDbId > 6) {
	$scriptDbId = 1;
}
// These are needed for $jsTag
$translationFontSize = 0;
$translationFontSize2 = 0;
$translationFontSize3 = 0;
$translationFontSize4 = 0;
$transliterationFontSize = 0;
$tafsirFontSize = 0;
$translationDbIds = array();
if (isset($_GET["tid"])) {
	$explodedTids = explode(",", $_GET["tid"]);
	StringUtils::isNumericArray($explodedTids, true);
	$translationDbIds = $explodedTids;
}
$translationDbIds = array_slice($translationDbIds, 0, 4); // Max 4 translations allowed
$translationsData = array();

$i = 0;
foreach ($translationDbIds as $tid) {
	if ($tid == 0) {
		continue;
	}
	$metaRecord = QuranMetaQueries::queryActiveById($tid);
	if ($metaRecord == null) {
		continue;
	}
	if (Debug::on()) {
		die(json_encode($metaRecord));
	}
	$i++;
	$data = array();
	$data["id"] = $tid;
	$data["name"] = $metaRecord->name;
	$data["direction"] = $metaRecord->direction;
	$data["cssClass"] = $metaRecord->cssClass;
	$data["numberingStyle"] = $metaRecord->numberingStyle;
	$paramName = "tfs" . $i;
	$data["fontSize"] = isset($_GET[$paramName]) && is_numeric($_GET[$paramName]) && intval($_GET[$paramName]) > 3 ? $_GET[$paramName] : $metaRecord->fontSize;
	if ($i == 1) $translationFontSize = $data["fontSize"];
	else if ($i == 2) $translationFontSize2 = $data["fontSize"];
	else if ($i == 3) $translationFontSize3 = $data["fontSize"];
	else if ($i == 4) $translationFontSize4 = $data["fontSize"];
	$translationsData[] = $data;
}
$tafsirDbId = isset($_GET["tafid"]) && is_numeric($_GET["tafid"]) ? $_GET["tafid"] : 0;
$tafsirData = array();
$hasTafsir = $tafsirDbId != $config->QURAN_HIDE_ID && $tafsirDbId != 0;
if ($hasTafsir) {
	$metaRecord = QuranMetaQueries::queryActiveById($tafsirDbId);
	if ($metaRecord != null) {
		$tafsirData["id"] = $tafsirDbId;
		$tafsirData["name"] = $metaRecord->name;
		$tafsirData["direction"] = $metaRecord->direction;
		$tafsirData["cssClass"] = $metaRecord->cssClass;
		$tafsirData["numberingStyle"] = $metaRecord->numberingStyle;
		$tafsirData["fontSize"] = isset($_GET["taffs"]) && is_numeric($_GET["taffs"]) && intval($_GET["taffs"]) > 3 ? $_GET["taffs"] : $metaRecord->fontSize;
		$tafsirFontSize = $tafsirData["fontSize"];
	}
} else {
	$hasTafsir = false;
}
$transliterationDbId = isset($_GET["triid"]) && is_numeric($_GET["triid"]) ? $_GET["triid"] : 0;
$transliterationData = array();
$hasTransliteration = $transliterationDbId != $config->QURAN_HIDE_ID && $transliterationDbId != 0;
if ($hasTransliteration) {
	$metaRecord = QuranMetaQueries::queryActiveById($transliterationDbId);
	if ($metaRecord != null) {
		$transliterationData["id"] = $transliterationDbId;
		$transliterationData["name"] = $metaRecord->name;
		$transliterationData["direction"] = $metaRecord->direction;
		$transliterationData["cssClass"] = $metaRecord->cssClass;
		$transliterationData["numberingStyle"] = $metaRecord->numberingStyle;
		$transliterationData["fontSize"] = isset($_GET["trifs"]) && is_numeric($_GET["trifs"]) && intval($_GET["trifs"]) > 3 ? $_GET["trifs"] : $metaRecord->fontSize;
		$transliterationFontSize = $transliterationData["fontSize"];
	}
} else {
	$hasTransliteration = false;
}
if (isset($_GET["ofs"]) && is_numeric($_GET["ofs"]) && intval($_GET["ofs"]) > 3) {
	$originalFontSize = $_GET["ofs"];
} else {
	$metaRecord = QuranMetaQueries::queryActiveById(1);
	$originalFontSize = $metaRecord->fontSize;
}
$englishFontSize = isset($_GET["efs"]) && is_numeric($_GET["efs"]) && intval($_GET["efs"]) > 3 ? $_GET["efs"] : "22";

$ayahNumberingEnabled = isset($_GET["n"]) ? $_GET["n"] : 1;
$GLOBALS["ayahNumberingEnabled"] = $ayahNumberingEnabled;
$referencingEnabled= isset($_GET["ref"]) ? $_GET["ref"] : 1;
$style = isset($_GET["style"]) ? $_GET["style"] : 0;
$scriptStyle = isset($_GET["scr"]) ? $_GET["scr"] : 1;
$GLOBALS["scriptStyle"] = $scriptStyle;
$hideScript = isset($_GET["hidescript"]);
$expandAllFootnotes = isset($_GET["exfn"]);
$expandAllFootnotesJsBool = $expandAllFootnotes ? "true" : "false";
switch ($scriptStyle) {
case 1:
	$scriptStyleClass = "script-uthmani";
	break;
case 2:
	$scriptStyleClass = "script-indopak";
	break;
case 4:
	$scriptStyleClass = "script-amiri";
	break;
case 5:
	$scriptStyleClass = "script-me-quran";
	break;
case 6:
	$scriptStyleClass = "script-islamicfont";
	break;
case 7:
	$scriptStyleClass = "script-lateef";
	break;
case 8:
	$scriptStyleClass = "script-scheherazade";
	break;
case 9:
	$scriptStyleClass = "script-mry";
	break;
case 10:
	$scriptStyleClass = "script-qalam";
  break;
case 11:
  $scriptStyleClass = "script-kingfahd";
  break;
default:
	$scriptStyleClass = "script-lateef";
}
$hasTitle = isset($_GET["title"]);
$hasTitleMeaning = isset($_GET["title-mean"]);
$hasBismillah = isset($_GET["bism"]);
$noSeparators = isset($_GET["nosep"]);
$noLabels = isset($_GET["nolabels"]);
$noRukuh = isset($_GET["norukuh"]);
$GLOBALS["noRukuh"] = $noRukuh;
$hasWordByWord = isset($_GET["wrd"]) ? $_GET["wrd"] : 0;
$title = "Surah $surahEnglishPronName";
if ($ayahStart == $ayahEnd) {
	$title .= " $surah:$ayahStart";
} else if ($ayahStart > 1 || $ayahEnd < $totalAyahs) {
	$title .= " $surah:$ayahStart-$ayahEnd";
}
$translationParams = "";
$availableTranslations = count($translationsData);
$hasTranslation = $availableTranslations > 0;
if ($availableTranslations > 0) $translationParams .= "tid=" . $translationsData[0]["id"];
if ($availableTranslations > 1) $translationParams .= "," . $translationsData[1]["id"];
if ($availableTranslations > 2) $translationParams .= "," . $translationsData[2]["id"];
if ($availableTranslations > 3) $translationParams .= "," . $translationsData[3]["id"];
$referenceString = $title;
if (true) {
	$referenceAnchorTagStart = "<a style='text-decoration:none;color:black;' target='_parent' href='$config->DOMAIN/$surah/$ayahStart";
	$referenceAnchorTagStart .= ($ayahEnd > $ayahStart ? "-$ayahEnd" : "");
	$referenceAnchorTagStart .= "?sty=$style";
	if ($hasTafsir) {
		$referenceAnchorTagStart .= "&tafid=$tafsirDbId";
	}
	$referenceAnchorTagStart .= "&$translationParams";
	if ($hasTransliteration) {
		$referenceAnchorTagStart .= "&triid=$transliterationDbId";
	}
	$referenceAnchorTagStart .= "&script=$scriptStyle";
	if ($hideScript) {
		$referenceAnchorTagStart .= "&hidescript";
	}
	if ($noSeparators) {
		$referenceAnchorTagStart .= "&nosep";
	}
	$referenceAnchorTagStart .= "&wrd=$hasWordByWord&qscr=$scriptDbId'>";
	$referenceAnchorTagEnd = "</a>";
}
$referenceLink = $referenceAnchorTagStart . $referenceString . $referenceAnchorTagEnd;
//--------------------------------------------- SOME REUSEABLE FUNCTIONS ---------------------------------------------
function rukuhCheck($ayah)
{
	global $surah;
	global $noRukuh;
	global $rukuhInfoArray;
	$rukuhSymbol = $GLOBALS["specialChars"][2];
	if (in_array($ayah, $rukuhInfoArray)) {
		return " <sup onselectstart='return false' title='Rukuh' notooltip class='rukuh " . ($noRukuh ? "hide-feature" : "") ."'><span class='script-uthmani'>$rukuhSymbol</span></sup>";
	}
	return "";
}

function getNumberingStyle() {
	global $scriptStyle;
	switch ($scriptStyle) {
	case 1: // Uthmani
	case 6: // Islamic Font
	case 5: // Medina Mushaf
	case 7: // Lateef
	case 4: // Amiri
	case 8: // Scheherazade
	case 9: // mry
  case 10: // Al-Qalam
  case 11: // King Fahd
		return "1";
	case 2: // Indo pak
		return "2";
	default:
		return "0";
	}
}
function getAyahNumber($numbStyle, $ayah, $isArabicText = false)
{
	$number = StringUtils::convertNumber("$ayah", $numbStyle, $isArabicText);
	$openBracket = $numbStyle == 2 ? $GLOBALS["specialChars"][1] : $GLOBALS["specialChars"][3];
	$closeBracket = $numbStyle == 2 ? $GLOBALS["specialChars"][0] : $GLOBALS["specialChars"][4];
	$result = "<span style='white-space: nowrap;' class='";
	if ($numbStyle == 2) {
		$result .= "script-kingfahd";
	} else {
		if ($isArabicText) {
			$result .= "script-kingfahd";
		} else {
			$result .= "script-eng";
		}
	}
	$result .= "'>" . ($isArabicText ? $openBracket : "(");
	$result .= $number;
	$result .= ($isArabicText ? $closeBracket : ")") . "</span>";
	return $result;
}
function getAyahSuffix($ayah, $isArabicText = false)
{
	global $ayahNumberingEnabled;
	global $surah;
	if ($ayahNumberingEnabled) {
		return rukuhCheck($ayah) . " " . getAyahNumber(getNumberingStyle(), "$ayah", $isArabicText) . "&nbsp;";
	}
	return rukuhCheck($surah, $ayah) . " ";
}
function convertAyahs(&$ayahObjectArray, $dataObject, $addAyahSuffix = false)
{
	global $ayahNumberingEnabled;
	$result = array();
	foreach ($ayahObjectArray as &$ayahObj) {
		$text = $ayahObj->text;
		if ($dataObject != null && $ayahNumberingEnabled) {
			$text .= getAyahNumber($dataObject["numberingStyle"], $ayahObj->ayah);
		}
		if ($addAyahSuffix) {
			$text .= getAyahSuffix($ayahObj->ayah, true);
		}
		$result[$ayahObj->ayah] = nl2br($text);
	}
	return $result;
}
function determineDirectionClass($dataObj)
{
	return $dataObj["direction"] == "lr" ? "lefttoright" : "righttoleft";
}
function ayahToHtml($key, $text)
{
	return "<span class='ayah-$key'>$text</span>";
}
//--------------------------------------------- PULL DATA ---------------------------------------------
$arabicAyahs = array();
if (!$hideScript) {
	$ayahsInArabic = AyahQueries::queryBySurahStartEndAndDatabase($surah, $ayahStart, $ayahEnd, $scriptDbId);
	$arabicAyahs = convertAyahs($ayahsInArabic, null, true);
}
$translationAyahs = array();
if ($hasTranslation) {
	foreach ($translationsData as &$translationData) {
		$tid = $translationData["id"];
		$ayahsTranslation = AyahQueries::queryBySurahStartEndAndDatabase($surah, $ayahStart, $ayahEnd, $tid);
		if (count($ayahsTranslation) > 0) {
			$translationAyahs[$tid] = convertAyahs($ayahsTranslation, $translationData);
			if ($hideScript && count($arabicAyahs) === 0) {
				$arabicAyahs = $translationAyahs[$tid];
			}
		}
	}
}

$transliterationAyahs = array();
if ($hasTransliteration) {
	$triid = $transliterationData["id"];
	$ayahsTransliteration = AyahQueries::queryBySurahStartEndAndDatabase($surah, $ayahStart, $ayahEnd, $triid);
	if (count($ayahsTransliteration) > 0) {
		$transliterationAyahs = convertAyahs($ayahsTransliteration, $transliterationData);
	}
}
$tafsirAyahs = array();
if ($hasTafsir) {
	$tafid = $tafsirData["id"];
	$ayahsTafsir = AyahQueries::queryBySurahStartEndAndDatabase($surah, $ayahStart, $ayahEnd, $tafid);
	if (count($ayahsTafsir) > 0) {
		$tafsirAyahs = convertAyahs($ayahsTafsir, $tafsirData);
	}
}
$words = array();
if ($hasWordByWord) {
	$wordsData = QuranWordQueries::queryBySurahStartEnd($surah, $ayahStart, $ayahEnd);
	foreach ($wordsData as &$quranWord) {
		$words[$quranWord->ayah][$quranWord->wordId] = $quranWord;
	}
}
// --------------------------------------------CONTENTS WIDTH-------------------------------------

$widthPercentageArabic = $hideScript ? 100 : 50;
$widthPercentageOther = $hideScript ? 100 : 50;
if ($hasTranslation && $hasTafsir && $hasTransliteration && !$hasWordByWord) {
	$widthPercentageArabic = 25;
	$widthPercentageOther = 25;
} else if (($hasTranslation && $hasTafsir && !$hasTransliteration && !$hasWordByWord) ||
	($hasTranslation && !$hasTafsir && $hasTransliteration && !$hasWordByWord) ||
	(!$hasTranslation && $hasTafsir && $hasTransliteration && !$hasWordByWord)) {
	$widthPercentageArabic = 34;
	$widthPercentageOther = 33;
} else if (($hasTranslation && !$hasTafsir && !$hasTransliteration && !$hasWordByWord) ||
	(!$hasTranslation && $hasTafsir && !$hasTransliteration && !$hasWordByWord) ||
	(!$hasTranslation && !$hasTafsir && $hasTransliteration && !$hasWordByWord)) {
	$widthPercentageArabic = 50;
	$widthPercentageOther = 50;
} else if ($hasTranslation && $hasTafsir && $hasTransliteration && $hasWordByWord) {
	$widthPercentageArabic = 20;
	$widthPercentageOther = 20;
} else if (($hasTranslation && $hasTafsir && !$hasTransliteration && $hasWordByWord) ||
	($hasTranslation && !$hasTafsir && $hasTransliteration && $hasWordByWord) ||
	(!$hasTranslation && $hasTafsir && $hasTransliteration && $hasWordByWord) ||
	($hasTranslation && $hasTafsir && $hasTransliteration && !$hasWordByWord)) {
	$widthPercentageArabic = 25;
	$widthPercentageOther = 25;
} else if (($hasTranslation && !$hasTafsir && !$hasTransliteration && $hasWordByWord) ||
	(!$hasTranslation && $hasTafsir && !$hasTransliteration && $hasWordByWord) ||
	(!$hasTranslation && !$hasTafsir && $hasTransliteration && $hasWordByWord) ||
	($hasTranslation && !$hasTafsir && $hasTransliteration && !$hasWordByWord)) {
	$widthPercentageArabic = 34;
	$widthPercentageOther = 33;
}
if ($availableTranslations > 1) {
	$widthPercentageArabic /= $availableTranslations;
	$widthPercentageOther /= $availableTranslations;
}
//--------------------------------------------- CONTENTS ---------------------------------------------
if ($includeMeta) {
	$contents .= <<<PAGE
	<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>
	<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en'>
	<head>
		<meta content='utf-8' http-equiv='encoding'>
		<meta content='text/html;charset=utf-8' http-equiv='Content-Type'>
		<link rel='stylesheet' type='text/css' href='/styles/layout.css?4&v=$config->VERSION+3' />
		<link rel='stylesheet' type='text/css' href='/styles/quran.css?f&v=$config->VERSION+2' />
		<script src='/scripts/jquery-2.1.0.min.js?v=$config->VERSION'></script>
		<title>$title - amrayn</title>
	</head>
	<body>
PAGE;
}

$contents .= '
<style>
	@page {
	  @bottom-right {
		content: counter(page) " of " counter(pages);
	  }
	}
	.reference-footer {
		display:' . ($referencingEnabled ? "block" : "none") . ';
	}
	.reference-footer-tr {
		display:' . ($referencingEnabled ? "table-row" : "none") . ';
	}
	.cut-title {
		position: relative;
		overflow:hidden;
		height: 70px;
		top: 9px;
		z-index: -1000;
		text-align: center;
	}
	.cut-title > img {
		position: relative;
    	top: -51px;
    	width: 215px;
	}
</style>';
$contents .= "<div id='main-contents-head'>";
if ($hasTitle) {
    if (isset($_GET["txttitle"])) {
	$finalTitleSize = intval($originalFontSize) + 5;
	$contents .= "<div style='font-family: Cookie, cursive;text-align:center;font-size:$finalTitleSize" . "px;color:#3F260A;  padding-top: 0.5em;'>" . $surahNameArabicInfo . "</div>";
    } else {
	$contents .= "<div class='cut-title'><img src='https://cdn.amrayn.com/qimages-c/$surah.svg'></div>";
    }
}
if ($hasTitleMeaning) {
	$finalTitleSize = intval($englishFontSize) + 5;
	$contents .= "<div style='font-family: Cookie, cursive;text-align:center;font-size:$finalTitleSize" . "px;color:#595959;  padding-top: 0.5em;'>" . $surahNameMeaningInfo . "</div>";
}
if ($hasBismillah) {
	if ($hasTitle || $hasTitleMeaning) {
		$contents .= "<br/>";
	}
	$contents .= "<div class='arabic script-uthmani' style='text-align:center;color:#595959;font-size:32px;'>&#65021;";
	// $contents .= "بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ";
	$contents .= "</div>";
}
$contents .= "</div>";
switch ($style) {
case 1: // Side-by-side
	$separatorClass = $noSeparators ? "" : "q-sep1";
	$contents .= "<table width='100%' id='main-contents' cellpadding='0' cellspacing='0' style='visibility:hidden'>";
	$rowAlternator = 1;
	foreach($arabicAyahs as $key => $value) {
		$contents .= "<tr";
		if (++$rowAlternator % 2 == 1) {
			$contents .= " class='ayah-alt2'";
		} else {
			$contents .= " class='ayah-alt'";
		}
		$contents .= ">";

		if ($hasTransliteration) {
			$contents .= "<td class='$separatorClass' style='vertical-align:top;width:$widthPercentageOther%;'>";
			$contents .= "<div class='resource-label " . ($noLabels ? "hide-feature" : "") . "'>Transliteration - " . $transliterationData["name"] . "</div>";
			$contents .= "<div class='ayah-transliteration " . determineDirectionClass($transliterationData) . " " . $transliterationData["cssClass"] . "'>";
			$contents .= ayahToHtml($key, $transliterationAyahs[$key]);
			$contents .= "</div>";
			$contents .= "</td>";
		}
		if ($hasTafsir) {
			$contents .= "<td style='vertical-align:top;width:$widthPercentageOther%;' class='$separatorClass'>";
			$contents .= "<div class='resource-label " . ($noLabels ? "hide-feature" : "") . "'>Tafsir - " . $tafsirData["name"] . "</div>";
			$contents .= "<div class='ayah-tafsir " . determineDirectionClass($tafsirData) . " " . $tafsirData["cssClass"] . "'>";
			$contents .= ayahToHtml($key, $tafsirAyahs[$key]);
			$contents .= "</div>";
			$contents .= "</td>";
		}
		$idx = 0;
		foreach ($translationAyahs as $tid => $text) {
			$contents .= "<td style='vertical-align:top;width:$widthPercentageOther%;' class='$separatorClass'>";
			$contents .= "<div class='resource-label " . ($noLabels ? "hide-feature" : "") . "'>Translation - " . $translationsData[$idx]["name"] . "</div>";
			$contents .= "<div class='ayah-translation ayah-translation" . ($idx + 1) . " " . determineDirectionClass($translationsData[$idx]) . " " . $translationsData[$idx]["cssClass"] . "'" . ($noLabels ? " title='" . $translationData[$idx]["name"] : "") . "'>";
			$contents .= ayahToHtml($key, $translationAyahs[$tid][$key]);
			$contents .= "</div>";
			$contents .= "</td>";
			$idx++;
		}
		if ($hasWordByWord) {
			$contents .= "<td style='vertical-align:top;width:$widthPercentageOther%;' class='$separatorClass'>";
			$contents .= "<div class='resource-label " . ($noLabels ? "hide-feature" : "") . "'>Word by word</div>";
			$contents .= "<div><table border='0' class='ayah-$key' cellpadding='0' cellspacing='0' width='100%'>";
			if  (array_key_exists($key, $words)) {
				$quranWordObjects = $words[$key];
				for ($i = 1; $i <= count($quranWordObjects); $i++) {
					$quranWordObj = $quranWordObjects[$i];
					$contents .= "<tr><td align='center' class='ayah-word-by-word'><span class='word-by-word-mean script-eng'>" . $quranWordObj->meaning . "</span></td><td align='left' class='ayah-word-by-word'><span class='word-by-word-orig $scriptStyleClass'>" . $quranWordObj->arabic . "</span></td></tr>";
				}
			}
			$contents .= "</table></div>";
			$contents .= "</td>";
		}
		if (!$hideScript) {
			$contents .= "<td class='original-script' style='vertical-align:top;width:$widthPercentageArabic%;'>";
			$contents .= "<div class='resource-label label-quran " . ($noLabels || $hideScript ? "hide-feature" : "") . "'>The Noble Qur'an</div>";
			$contents .= "<div class='arabic $scriptStyleClass ayah'>";
			$contents .= ayahToHtml($key, $arabicAyahs[$key]);
			$contents .= "</div>";
			$contents .= "</td>";
		}
		$contents .= "</tr>";
	}
	//if ($reference) {
		$refStr = "<tr class='reference-footer-tr'><td colspan='5' style='vertical-align:top;width:100%;text-align:center'>";
		$refStr .= "<div style='text-align:center;font-size:" . $englishFontSize . "px;'>[";
		$refStr .= $referenceLink;
		$refStr .= "]</div>$embedLogo</td></tr>";
		$contents .= " $refStr";
	//}
	$contents .= "</table>";
	break;
case 2: // Ayah-by-ayah
	$separatorClass = ($hasTafsir || $availableTranslations > 1 || $hasTransliteration || $hasWordByWord) && !$noSeparators ? "q-sep2" : "";
	$contents .= "<table width='100%' id='main-contents' cellpadding='0' cellspacing='0' style='visibility:hidden'>";
	$rowAlternator = 1;
	// Arabic
	foreach($arabicAyahs as $key => $value) {
		$rowAlternator++;
		$contents .= "<tr";
		if ($rowAlternator % 2 == 1) {
			$contents .= " class='ayah-alt2'";
		} else {
			$contents .= " class='ayah-alt'";
		}
		$contents .= ">";
		$contents .= "<td class='original-script $separatorClass' name='$key' style='vertical-align:top;width:100%;'>";
		$contents .= "<div class='resource-label label-quran " . ($noLabels || $hideScript ? "hide-feature" : "") . "'>The Noble Qur'an</div>";
		if (!$hideScript) {
			$contents .= "<div class='arabic $scriptStyleClass ayah'>";
			$contents .= ayahToHtml($key, $arabicAyahs[$key]);
			$contents .= "</div>";
		}
		$contents .= "</td>";
		$contents .= "</tr>";
		if ($hasWordByWord) {
			$contents .= "<tr";
			if ($rowAlternator % 2 == 1) {
				$contents .= " class='ayah-alt2'";
			} else {
				$contents .= " class='ayah-alt'";
			}
			$contents .= ">";
			$contents .= "<td style='vertical-align:top;width:100%;' class='";
			if ($hasTransliteration || $availableTranslations > 1 || $hasTafsir) {
				$contents .= "$separatorClass";
			}
			$contents .= "'>";
			$contents .= "<div class='resource-label " . ($noLabels ? "hide-feature" : "") . "'>Word by word</div>";
			$contents .= "<div class='ayah-word-by-word arabic $scriptStyleClass'>";
			if  (array_key_exists($key, $words)) {
				$quranWordObjects = $words[$key];
				for ($i = 1; $i <= count($quranWordObjects); $i++) {
					$quranWordObj = $quranWordObjects[$i];
					$contents .= "<div class='word-by-word-style-0-2-div'><span class='ayah-$key ayah-word-by-word'>";
					$contents .= "<span class='word-by-word-orig $scriptStyleClass'>" . $quranWordObj->arabic . "</span><br/><span class='word-by-word-mean script-eng'><bdo dir='ltr'>" . $quranWordObj->meaning . "</bdo></span>";
					$contents .= "</span></div>";
				}
			}
			$contents .= "<span class='ayah-$key word-by-word-mean' style='padding-left:0.25em;padding-right:0.25em;'>" . getAyahNumber(getNumberingStyle(), $key, true) . "</span> ";
			$contents .= "</div>";
			$contents .= "</td></tr>";
		}
		if ($hasTransliteration) {
			$contents .= "<tr";
			if ($rowAlternator % 2 == 1) {
				$contents .= " class='ayah-alt2'";
			} else {
				$contents .= " class='ayah-alt'";
			}
			$contents .= ">";
			$contents .= "<td style='vertical-align:top;width:100%;' class='";
			if ($availableTranslations > 1 || $hasTafsir) {
				$contents .= "$separatorClass";
			}
			$contents .= "'>";
			$contents .= "<div class='resource-label " . ($noLabels ? "hide-feature" : "") . "'>Transliteration - " . $transliterationData["name"] . "</div>";
			$contents .= "<div class='ayah-transliteration " . determineDirectionClass($transliterationData) . " " . $transliterationData["cssClass"] . "'>";
			$contents .= ayahToHtml($key, $transliterationAyahs[$key]);
			$contents .= "</div>";
			$contents .= "</td></tr>";
		}
		$idx = 0;
		foreach ($translationAyahs as $tid => $text) {
			$contents .= "<tr";
			if ($rowAlternator % 2 == 1) {
				$contents .= " class='ayah-alt2'";
			} else {
				$contents .= " class='ayah-alt'";
			}
			$contents .= ">";
			$contents .= "<td style='vertical-align:top;width:100%;' class='";
			if ($idx + 1 < $availableTranslations || $hasTafsir) {
				$contents .= "$separatorClass";
			}
			$contents .= "'>";
			$contents .= "<div class='resource-label " . ($noLabels ? "hide-feature" : "") . "'>" . $translationsData[$idx]["name"] . "</div>";
			$contents .= "<div class='ayah-translation ayah-translation" . ($idx + 1) . " " . determineDirectionClass($translationsData[$idx]) . " " . $translationsData[$idx]["cssClass"] . "'>";

			$contents .= ayahToHtml($key, $translationAyahs[$tid][$key]);
			$contents .= "</div>";
			$contents .= "</td></tr>";
			$idx++;
		}
		if ($hasTafsir) {
			$contents .= "<tr";
			if ($rowAlternator % 2 == 1) {
				$contents .= " class='ayah-alt2'";
			} else {
				$contents .= " class='ayah-alt'";
			}
			$contents .= ">";
			// Note no separator after tafsir because its last row
			$contents .= "<td style='vertical-align:top;width:100%;'>";
			$contents .= "<div class='resource-label " . ($noLabels ? "hide-feature" : "") . "'>Tafsir - " . $tafsirData["name"] . "</div>";
			$contents .= "<div class='ayah-tafsir " . determineDirectionClass($tafsirData) . " " . $tafsirData["cssClass"] . "'>";
			$contents .= ayahToHtml($key, $tafsirAyahs[$key]);
			$contents .= "</div>";
			$contents .= "</td></tr>";
		}
	}
	$refStr = "<tr class='reference-footer-tr'><td style='vertical-align:top;width:100%;text-align:center'>";
	$refStr .= "<div style='text-align:center;font-size:" . $englishFontSize . "px;'>[";
	$refStr .= $referenceLink;
	$refStr .= "]</div>$embedLogo</td></tr>";
	$contents .= " $refStr";
	$contents .= "</table>";
	break;
default: // Continues
	$separatorClass = ($hasTafsir || $availableTranslations > 1 || $hasTransliteration || $hasWordByWord) && !$noSeparators ? "q-sep3" : "";
	$contents .= "<div id='main-contents' style='visibility:hidden'>";
	if (!$hideScript) {
		$contents .= "<div class='resource-label label-quran " . ($noLabels ? "hide-feature" : "") . "'>The Noble Qur'an</div>";
		$contents .= "<div class='original-script arabic $scriptStyleClass ayah $separatorClass' style='text-align:justify'>";
		foreach ($arabicAyahs as $key => $ayah) {
			$contents .= ayahToHtml($key, $ayah);
		}
		$contents .= "</div>";
	}
	if ($hasWordByWord) {
		$contents .= "<div class='resource-label " . ($noLabels ? "hide-feature" : "") . "'>Word by word</div>";
		$contents .= "<div class='ayah-word-by-word arabic $scriptStyleClass $separatorClass'>";
		foreach ($arabicAyahs as $key => $ayah) {
			if  (array_key_exists($key, $words)) {
				$quranWordObjects = $words[$key];
				$wordsCount = count($quranWordObjects);
				for ($i = 1; $i <= $wordsCount; $i++) {
					$quranWordObj = $quranWordObjects[$i];
					$contents .= "<div class='word-by-word-style-0-2-div'";
					if ($i == $wordsCount) {
						$contents .= " style='border:none'";
					}
					$contents .= "><span class='ayah-$key'>";
					$contents .= "<span class='word-by-word-orig'>" . $quranWordObj->arabic . "</span><br/><span class='word-by-word-mean script-eng'><bdo dir='ltr'>" . $quranWordObj->meaning . "</bdo></span></span></div>";
				}
			}
			$contents .= "<span class='ayah-$key word-by-word-mean' style='border-left: 2px solid #333333;padding-left:0.25em;padding-right:0.25em;'>" . getAyahNumber(getNumberingStyle(), $key, true) . "</span> ";
		}
		$contents .= "</div>";
	}
	if ($hasTransliteration) {
		$contents .= "<br/>";
		$contents .= "<div class='resource-label " . ($noLabels ? "hide-feature" : "") . "'>Transliteration - " . $transliterationData["name"] . "</div>";
		$contents .= "<div class='ayah-transliteration " . determineDirectionClass($transliterationData) . " " . $transliterationData["cssClass"] . " $separatorClass' style='text-align:justify'>";
		foreach ($transliterationAyahs as $key => $text) {
			$contents .= ayahToHtml($key, $text);
			$contents .= "&nbsp;";
		}
		$contents .= "</div>";
	}
	$idx = 0;
	foreach ($translationAyahs as $tid => $text) {
		$contents .= "<br/>";
		$contents .= "<div class='resource-label " . ($noLabels ? "hide-feature" : "") . "'>Translation - " . $translationsData[$idx]["name"] . "</div>";
		$contents .= "<div class='ayah-translation ayah-translation" . ($idx + 1) . " " . determineDirectionClass($translationsData[$idx]) . " " . $translationsData[$idx]["cssClass"] . " " . (($idx + 1 < $availableTranslations || $hasTafsir) ? "$separatorClass'" : "") . "' " . ($noLabels ? " title='" . $translationsData[$idx]["name"] : "") . "' style='text-align:justify'>";
		foreach ($translationAyahs[$tid] as $key => $ayah) {
			$contents .= ayahToHtml($key, $ayah);
			$contents .= "&nbsp;";
		}
		$contents .= "</div>";
		$idx++;
	}
	if ($hasTafsir) {
		$contents .= "<br/>";
		$contents .= "<div class='resource-label " . ($noLabels ? "hide-feature" : "") . "'>Tafsir - " . $tafsirData["name"] . "</div>";
		$contents .= "<div class='ayah-tafsir " . determineDirectionClass($tafsirData) . " " . $tafsirData["cssClass"] . "' style='text-align:justify'>";
		foreach ($tafsirAyahs as $key => $text) {
			$contents .= ayahToHtml($key, $text);
			$contents .= "&nbsp;";
		}
		$contents .= "</div>";
	}
	break;
}

$jsTag = <<<PAGE
		findAyah = function(ayahNumber) { return document.getElementsByClassName("ayah-" + ayahNumber); }
		findHighlightedAyahs = function() { return document.getElementsByClassName("highlighted-ayah"); }
		ayahCount = function() { return ($ayahEnd-$ayahStart) + 1; }
		currentSurah = function() { return $surah; }
		ayahStart = function() { return $ayahStart; }
		ayahEnd = function() { return $ayahEnd; }
		totalVerses = function() { return $totalAyahs; }
		tafsirId = function() { return $tafsirDbId;}

		unhighlightAll = function() {
			$(".highlighted-ayah").removeClass("highlighted-ayah");
		}
		unhighlightAyah = function(ayahNumber) {
			$(".ayah-" + ayahNumber).removeClass("highlighted-ayah");
		}
		highlightAyah = function(ayahNumber) {
			unhighlightAll();
			$(".ayah-" + ayahNumber).addClass("highlighted-ayah");
		}
		var Font = {
			__getSize: function(selector, defaultSize) {
				var isValidSelector = $(selector).length > 0;
				if (isValidSelector) {
					var size = $(selector).css("font-size");
					if (size == undefined) {
						return defaultSize;
					}
					if (size.indexOf("px") > -1) {
						return size.substr(0, size.indexOf("px"));
					}
					// as is
					return size;
				}
				return defaultSize;
			},
			__setSize: function(selector, newSize) {
				var isValidSelector = $(selector).length > 0;
				if (isValidSelector) {
					$(selector).css("font-size", newSize + "px");
				}
			},
			__changeBy: function(selector, deltaValue, defaultValue) {
				var isValidSelector = $(selector).length > 0;
				if (isValidSelector) {
					var size = $(selector).css("font-size");
					var newSize = defaultValue;
					if (size != undefined && size.indexOf("px") > -1) {
						size = size.substr(0, size.indexOf("px"));
						newSize = parseInt(size) + deltaValue;
					}
					$(selector).css("font-size", newSize + "px");
				}
			},
			refreshAll: function() {
				Font.__setSize(".ayah", "$originalFontSize");
				Font.__setSize(".ayah-translation1", "$translationFontSize");
				Font.__setSize(".ayah-translation2", "$translationFontSize2");
				Font.__setSize(".ayah-translation3", "$translationFontSize3");
				Font.__setSize(".ayah-translation4", "$translationFontSize4");
				Font.__setSize(".ayah-transliteration", "$transliterationFontSize");
				Font.__setSize(".ayah-tafsir", "$tafsirFontSize");
				Font.__setSize(".word-by-word-orig", "$originalFontSize");
				Font.__setSize(".word-by-word-mean", "$englishFontSize");
			},
			__changeAllBy: function(deltaValue) {
				Font.__changeBy(".ayah", deltaValue, "$originalFontSize");
				Font.__changeBy(".ayah-translation1", deltaValue, "$translationFontSize");
				Font.__changeBy(".ayah-translation2", deltaValue, "$translationFontSize2");
				Font.__changeBy(".ayah-translation3", deltaValue, "$translationFontSize3");
				Font.__changeBy(".ayah-translation4", deltaValue, "$translationFontSize4");
				Font.__changeBy(".ayah-transliteration", deltaValue, "$transliterationFontSize");
				Font.__changeBy(".ayah-tafsir", deltaValue, "$tafsirFontSize");
				Font.__changeBy(".word-by-word-orig", deltaValue, "$originalFontSize");
				Font.__changeBy(".word-by-word-mean", deltaValue, "$englishFontSize");
			},
			increment: function() {
				Font.__changeAllBy(1);
			},
			decrement: function() {
				Font.__changeAllBy(-1);
			},
			originalSize: function() {
				return Font.__getSize(".ayah", "$originalFontSize");
			},
			englishSize: function() {
				return Font.__getSize(".title-mean", "$englishFontSize");
			},
			translation1Size: function() {
				return Font.__getSize(".ayah-translation1", "$translationFontSize");
			},
			translation2Size: function() {
				return Font.__getSize(".ayah-translation2", "$translationFontSize2");
			},
			translation3Size: function() {
				return Font.__getSize(".ayah-translation3", "$translationFontSize3");
			},
			translation4Size: function() {
				return Font.__getSize(".ayah-translation4", "$translationFontSize4");
			},
			transliterationSize: function() {
				return Font.__getSize(".ayah-transliteration", "$transliterationFontSize");
			},
			tafsirSize: function() {
				return Font.__getSize(".ayah-tafsir", "$tafsirFontSize");
			}
		};
		triggerVisiblility = function(selector, val) {
			$(selector).css("display", val ? "block" : "none");
		}
		triggerTranslation = function(val) {
			triggerVisiblility(".ayah-translation", val);
		}
		triggerTransliteration = function(val) {
			triggerVisiblility(".ayah-transliteration", val);
		}
		triggerTafsir = function(val) {
			triggerVisiblility(".ayah-tafsir", val);
		}
		frameLoadComplete = function(callback) {
			Font.refreshAll();
			$("#main-contents").css("visibility", "visible");

			$(".show-foot-note").click(function() { showFootnote($(this)); });
			$(".hide-foot-note").click(function() { hideFootnote($(this)); });
			if ($expandAllFootnotesJsBool) {
				$(".show-foot-note").click();
				if ($isPdfPrintOrImageRenderJsBool) {
					$(".hide-foot-note").each(function() {
						var showText = $("#" + $(this).attr("id").substr("hide-".length)).text();
						$(this).text(showText.substr(0, showText.length - 1));
					});
				}
			}
			// Foot notes numbers
			$(".taf-foot").each(function(idx, elem){
				$(this).prepend("<span class='n'>" + (idx + 1) + "</span>")
			});
			// Foot notes click event
			$(".taf-foot > .n").on("click", function() {
				$(this).next().css("left", $(this).position().left);
				$(this).next().css("display", "inline");
				$(this).next().click(function() {
					$(this).css("display", "none");
				});
			});
			// Quran Reference links
			var links = $(".footnote-qref-link");
			for (var i = 0; i < links.length; ++i) {
				var curr = $(links[i]);
				if (curr.attr("lf") == "true") {
					continue;
				}
				var text = curr.text();
				var surahId = text.substr(0, text.indexOf(":"));
				var verses = text.substr(text.indexOf(":") + 1);
				curr.attr("lf", true);
				curr.attr("href", "/" + text.replace(":", "/") + "?tafid=" + tafsirId());
				curr.attr("target", "_blank");
				if (typeof CHAPTER_NAMES_ENGLISH !== "undefined") {
					curr.text(CHAPTER_NAMES_ENGLISH[surahId] + " [" + surahId + ":" + verses + "]");
				}
			}

			if ($isPdfPrintOrImageRenderJsBool) {
				// We hide all the reference for image or pdf since they are useless
				$(".taf-foot").hide();
			}
			if (typeof callback === "function") {
				callback();
			}
		}
		hideFootnote = function(obj) {
			$("#" + obj.attr("id").substr("hide-link-".length)).hide();
			$("#link-" + obj.attr("id").substr("hide-link-".length)).show();
PAGE;
				  if (isset($_GET["fnclr"])) {
					  $jsTag .= '$("#" + obj.attr("id").substr("link-".length)).css("background", "none");';
				  }
$jsTag .= <<<PAGE
		}
		showFootnote = function(obj) {
			$("#" + obj.attr("id").substr("link-".length)).show();
PAGE;
				  if (isset($_GET["fnclr"])) {
					  $jsTag .= '$("#" + obj.attr("id").substr("link-".length)).css("background", "rgb(" + (Math.floor((256-194)*Math.random()) + 230) + "," + (Math.floor((256-194)*Math.random()) + 230) + "," + (Math.floor((256-194)*Math.random()) + 230) + ")");';
				  }
$jsTag .= <<<PAGE
			obj.hide();
		}

PAGE;

if ($includeMeta) {
	$jsTag .= "window.onload = frameLoadComplete;";
}

$contents .= "<script type='text/javascript'>$jsTag</script>";

if ($includeMeta) {
	$contents .= "</body></html>";
}
echo $contents;
?>
