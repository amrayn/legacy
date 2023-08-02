<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("pages/page.php");
includeOnce("core/queries/SurahInfoQueries.php");
includeOnce("core/queries/MushafAyahDataQueries.php");
includeOnce("core/queries/QuranStudiesQueries.php");

$config = Config::getInstance();

$surah = isset($_GET["surah"]) ? $_GET["surah"] : 1;
$surah = is_numeric($surah) && (int)$surah >= 1 && (int)$surah <= 114 ? $surah : 1;

$allSurahs = SurahInfoQueries::queryByTypes(array(SurahInfoTypes::SurahName, SurahInfoTypes::SurahNameMeaning, SurahInfoTypes::SurahNameEnglish));
$surahInfoResult = SurahInfoQueries::queryBySurah($surah);


$surahNameInfo = SurahInfo::getInfoByType(SurahInfoTypes::SurahName, $surahInfoResult, true);
$surahNameMeaningInfo = SurahInfo::getInfoByType(SurahInfoTypes::SurahNameMeaning, $surahInfoResult, true);
$surahNameEnglishInfo = SurahInfo::getInfoByType(SurahInfoTypes::SurahNameEnglish, $surahInfoResult, true);
$surahName = SurahInfo::getInfoByType(SurahInfoTypes::SurahNameEnglishPronounced, $surahInfoResult, true);
$revelationOrderInfo = SurahInfo::getInfoByType(SurahInfoTypes::RevelationOrder, $surahInfoResult, true);
$manzilInfo = SurahInfo::getInfoByType(SurahInfoTypes::ManzilSurah, $surahInfoResult, true);
$verseCountInfo = SurahInfo::getInfoByType(SurahInfoTypes::VerseCount, $surahInfoResult, true);
$sajdahInfo = SurahInfo::getInfoByType(SurahInfoTypes::AyahSajdah, $surahInfoResult);
$juzInfo = SurahInfo::getInfoByType(SurahInfoTypes::JuzSurah, $surahInfoResult, true);
$rukuhInfo = SurahInfo::getInfoByType(SurahInfoTypes::SurahRukuhList, $surahInfoResult);
$hizbInfo = SurahInfo::getInfoByType(SurahInfoTypes::HizbList, $surahInfoResult);
$revelationPeriodInfo = SurahInfo::getInfoByType(SurahInfoTypes::RevelationPeriod, $surahInfoResult, true);
$introDetails = SurahInfo::getInfoByType(SurahInfoTypes::IntroDetails, $surahInfoResult, true);
$totalPages = floatval(SurahInfo::getInfoByType(SurahInfoTypes::NumberOfPagesInMedinaMushaf, $surahInfoResult, true));

$title = "$surahName Introduction";
$isRevealedInMakkah = $revelationPeriodInfo == 2;

$pageNumberData = MushafAyahDataQueries::queryPageNumberForSurah($surah);
$pageNumber = "[Cannot retrieve]";
if ($pageNumberData !== null && count($pageNumberData) > 0) {
	$pageNumber = $pageNumberData[0]->data;
}

$statList = array(
		array("label" => "Meaning", "value" => $surahNameMeaningInfo),
		array("label" => "No. of Ayahs", "value" => $verseCountInfo),
		array("label" => "Revealed In", "value" => "<img src='https://amrayn.com/assets/images/" . ($isRevealedInMakkah ? "kaaba" : "green-dome") . ".png'  style='height:20px;width:auto' /> ". ($isRevealedInMakkah ? "Makkah" : "Medina")),
		array("label" => "Revelation Order", "value" => $revelationOrderInfo),
		array("label" => "Sajdah(s)", "value" => $sajdahInfo == null ? 0 : (count($sajdahInfo->asArray()) . " (Ayahs: " . str_replace(",", ", ", $sajdahInfo->info) . ")")),
		array("label" => "Rukuh", "value" => count($rukuhInfo->asArray()) . " (Ayahs: " . str_replace(",", ", ", $rukuhInfo->info) . ")"),
		array("label" => "Juz", "value" => str_replace(",", ", ", $juzInfo)),
		array("label" => "Manzil</sup>", "value" => $manzilInfo),
		array("label" => "Hizb Break", "value" => $hizbInfo == null ? 0 : (count($hizbInfo->asArray()) . " (Ayahs: " . str_replace(",", ", ", $hizbInfo->info) . ")")),
		array("label" => "Page# <sup class='inf-ref'>[1]</sup>", "value" => "$pageNumber <span class='hide-on-print'>(<a href='/quran/$surah' class='local'>Open</a>)</span>"),
		array("label" => "No. of Pages <sup class='inf-ref'>[1]</sup>", "value" => "$totalPages page" . ($totalPages > 1 ? "s" : ""))
	);
$quranStudies = null;
if (AccountUtils::isLoggedIn()) {
	$quranStudies = QuranStudiesQueries::queryUserIdAndSurah(AccountUtils::currentUser()->id, $surah);
}
if ($quranStudies !== null) {
	$quranStudies->totalAyahsInSurah = $verseCountInfo;
	$memorizationStatusValue = "";
	$daysToComplete = $quranStudies->daysToComplete($totalPages);
	if ($daysToComplete < 1) {
		$daysToCompleteStr = "half day";
	} else if ($daysToComplete == 1) {
		$daysToCompleteStr = "1 day";
	} else {
		$daysToCompleteStr = "$daysToComplete days";
	}
	$daysTarget = ceil($quranStudies->calculateTargetDays($totalPages));
	if ($daysTarget <= 1) {
		$daysTargetStr = "$daysTarget day";
	} else {
		$daysTargetStr = "$daysTarget days";
	}
	if ($quranStudies->isHifzCompleted()) {
		$memorizationStatusValue = "<span class='fa fa-check' style='color:darkgreen'></span> (Took $daysToCompleteStr)";
	} else {
		if ($daysToComplete < 0) {
			$memorizationStatusValue .= "<span style='color:red;'>";
		} else {
			$memorizationStatusValue .= "<span>";
		}
		if ($quranStudies->hifzStarted != null) {
			if ($daysToComplete < 1) {
				$daysToCompleteStr = abs($daysToComplete) . " days late";
			} else if ($daysToComplete < 0) {
				$daysToCompleteStr = "1 day late";
			} else if ($daysToComplete == 1) {
				$daysToCompleteStr = "1 day left";
			} else {
				$daysToCompleteStr = "$daysToComplete days left";
			}
		}
		$memorizationStatusValue .= "$daysToCompleteStr</span>";
	}
	$statList[] = array("label" => "Memorization Status", "value" => $memorizationStatusValue . " <span class='hide-on-print'>(<a href='/memorization#s$surah' class='local'>Open</a>)</span>");
}
$row = 1;
$rowClass = "row-odd";

$socialMediaContents = includeContents("pages/embedded/contents/social-media-share.php");

$contents = <<<PAGE
	<script>
		surahChanged = function() {
			Utils.fastPageLoad('/quran/intro/' + $("#cbo-surah").val());
		}
	</script>
	<style type='text/css'>
		div.intro {
			width:100%;
		}
		div.intro > table {
			table-layout: fixed;
			width: 100%;
		}
		div.intro > h2 {
			border-bottom: 1px solid #c8c6c2;
			width: 100%;
			font-weight: normal;
			font-family: TrocchiRegular !important;
			padding: 0 0 13px;
			margin: 10px 0 13px;
			font-size: 18px;
		}
		div.intro > table tr.row-even {
			background-color: rgba(200,200,200,0.3);
		}
		div.intro > table tr.row-odd {
			background-color: transparent;
		}
		div.intro > table td.row-label {
			font-weight: bold;
			width: 50%;
		}
		div.intro > table td.row-value {
			width: 50%;
		}
		div.notes {
			font-size: 0.9em;
		}
		sup.inf-ref {
			font-size: 0.6em;
		}
		@media (max-width: 799px) {
			#cbo-surah {
				width: 100% !important;
			}
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
	</style>
	<div style='text-align:center;' class='hide-on-print'>
	<select name="cbo-surah" id="cbo-surah" onchange="surahChanged()" class="script-uthmani quran-control-2" style="display:inline;width:400px;">
PAGE;
for ($i = 1; $i <= 114; ++$i) {
	$contents .= "<option value='$i' ";
	if ($i == $surah) {
		$contents .= "selected";
	}
	$contents .= ">$i. " . SurahInfo::getInfoByTypeAndSurah(SurahInfoTypes::SurahNameEnglish, $i, $allSurahs, true) . " - " . SurahInfo::getInfoByTypeAndSurah(SurahInfoTypes::SurahName, $i, $allSurahs, true) . " - " . SurahInfo::getInfoByTypeAndSurah(SurahInfoTypes::SurahNameMeaning, $i, $allSurahs, true) . "</option>";
}
$contents .= <<<PAGE
	</select>
	</div>
	<br/>
	<div class='cut-title'><img src='//cdn.amrayn.com/qimages/$surah.svg?'></div>
	<h1 class='sequential'>Introduction to $surahName</h1>
	<div class='hide-on-print center-on-small-devices center-on-medium-devices' style='text-align: right'>
		$socialMediaContents
	</div>
	<h2>Statistics</h2>
	<div class='intro'><table>
		<tr class='$rowClass'>
		<td class='row-label'>Surah #</td>
		<td class='row-value'>$surah</td>
		</tr>
PAGE;

$row++;
$rowClass = $row % 2 == 0 ? "row-even" : "row-odd";

foreach ($statList as $statItem) {
	$label = $statItem["label"];
	$value = $statItem["value"];
	$contents .= <<<PAGE
	<tr class='$rowClass'>
	<td class='row-label'>$label</td>
	<td class='row-value'>$value</td>
	</tr>
PAGE;
	$row++;
	$rowClass = $row % 2 == 0 ? "row-even" : "row-odd";
}
	$contents .= <<<PAGE
		<tr class='$rowClass'>
		<td class='row-label'>Link</td>
		<td class='row-value'><a class='local' href='$config->DOMAIN_VAGUE/$surah'>$config->DOMAIN/$surah</a></td>
		</tr>
		</table>
	</div>
	<br/>
		<div class='notes'>
			<div><b>[1]</b> Quran printed at King Fahd Glorious Quran Printing Complex (Medina Mushaf).</div>
		</div>
	<br/>
	<h2>Details from Tafheem-ul-Quran</h2>
PAGE;
$contents .= nl2br($introDetails);

init($contents, array(
	"title" => "$title",
	"context" => Context::Quran,
	"meta_description" => "Introduction to Surah $surahName. Statistics, historical background, period of revelation, subject of matter and theme of surah. Details are taken from Tafheem ul Quran by Syed Abul Ala Maududi (RA)."
));
?>
