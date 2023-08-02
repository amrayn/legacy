<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("pages/page.php");
includeOnce("core/utils/Utils.php");

includeOnce("core/queries/MushafAyahDataQueries.php");
includeOnce("core/queries/MushafPageDataQueries.php");
includeOnce("core/queries/SurahInfoQueries.php");
$config = Config::getInstance();

$allSurahs = SurahInfoQueries::queryByTypes(array(SurahInfoTypes::SurahName, SurahInfoTypes::SurahNameMeaning, SurahInfoTypes::SurahNameEnglish, SurahInfoTypes::VerseCount));

$isPageForced = isset($_GET["page"]) && is_numeric($_GET["page"]) && $_GET["page"] >= 1 && $_GET["page"] <= 604;
$ref = "1:1";
$surah = 1;
$ayah = 1;
if (isset($_GET["go"])) {
	$ref = Utils::buildQuranRef($_GET["go"], $allSurahs);
	$surah = $ref["surah"];
	$ayah = $ref["start"];
	$surahInfoResult = SurahInfoQueries::queryBySurah($surah);
	if ($surahInfoResult === null) {
		$surah = 1;
		$ayah = 1;
	} else {
		$totalVerses = SurahInfo::getInfoByType(SurahInfoTypes::VerseCount, $surahInfoResult, true);
		if (intval($ayah) > $totalVerses) {
			$ayah = $totalVerses;
		}
	}
}
if ($isPageForced) {
	$mushafPage = $_GET["page"];
} else {
	$pageNumberData = MushafAyahDataQueries::queryPageNumberForSurah($surah, $ayah);
	$mushafPage = "[Cannot retrieve]";
	if ($pageNumberData !== null && count($pageNumberData) > 0) {
		$mushafPage = $pageNumberData[0]->data;
	}
}

$origCurrentPage = $mushafPage;
if ($mushafPage % 2 == 0) {
	$pageSideClass = "right";
	$mushafPage2 = $mushafPage - 1;
	$currentPage = $mushafPage;
	$prevPage = $mushafPage - 2;
	$nextPage = $mushafPage + 1;
} else {
	$pageSideClass = "left";
	$mushafPageTmp = $mushafPage + 1;
	$mushafPage2 = $mushafPage;
	$mushafPage = $mushafPageTmp;
	$currentPage = $mushafPage2;
	$prevPage = $mushafPage2 - 1;
	$nextPage = $mushafPage2 + 2;
}
$pageData = MushafPageDataQueries::queryByPage($mushafPage);
$pageData2 = MushafPageDataQueries::queryByPage($mushafPage2);
$pageDataRefs = explode(",", $pageData->ayahRef);
$pageDataRefs2 = explode(",", $pageData2->ayahRef);
$highlightAyahJS = "true";
if ($isPageForced) {
	$firstRef = $pageDataRefs[0];
	$surah = explode(":", $firstRef)[0];
	$ayah = explode(":", $firstRef)[1];
	$highlightAyahJS = "false";
}

$surahInfoResult = SurahInfoQueries::queryBySurah($surah);
$arabicName = SurahInfo::getInfoByType(SurahInfoTypes::SurahName, $surahInfoResult, true);
$englishName = SurahInfo::getInfoByType(SurahInfoTypes::SurahNameEnglish, $surahInfoResult, true);


$surahCoords = MushafAyahDataQueries::queryByTypeAndSurah(MushafAyahDataTypes::Coordinates, $surah);
$orderedCoordsMap = array();
foreach ($surahCoords as $coord) {
	$orderedCoordsMap["$coord->surah:$coord->ayah"] = $coord->data;
}
$values = array();
$i = 0;
foreach ($pageDataRefs as &$data) {
	$values[$i++] = "'$data," . $orderedCoordsMap[$data] . "'";
}
$pageDataJs = "var pageDataLeft = [" . implode($values, ",") . "];";

$values2 = array();
$i = 0;
foreach ($pageDataRefs2 as &$data) {
	$values2[$i++] = "'$data," . $orderedCoordsMap[$data] . "'";
}
$pageDataJs2 = "var pageDataRight = [" . implode($values2, ",") . "];";


// Generate anchors on server side
$TOTAL_LINES = 15;
$LINE_HEIGHT = 38;
$MARGIN_TOP = 39.5;
$MARGIN_LEFT = 48.0168;
$DATA_LINE_HEIGHT = 41.5;

$anchors = array();
$stylesArr = array();
for ($i = 1; $i <= $TOTAL_LINES; ++$i) {
	$styles = array();
	$styles["top"] = ($MARGIN_TOP * $i);
	$styles["left"] = 408;
	$styles["width"] = 2;
	$styles["height"] = ($LINE_HEIGHT);
	$stylesArr[] = $styles;
}

$pageCoords = array();
foreach ($pageDataRefs2 as &$data) {
	$pageCoords[$data] = $orderedCoordsMap[$data];
}
$ayahRef = array();
$stylesArrByCoord = array();
$i = 0;
foreach ($pageCoords as $ref => $coord) {
	$styles = array();
	$x = explode(",", $coord)[0];
	$y = explode(",", $coord)[1];
	$styles["left"] = $x;
	$styles["top"] = ($y - ($LINE_HEIGHT / 1.5));
	$styles["height"] = ($LINE_HEIGHT);
	$styles["width"] = 2;
	$line = ceil($styles["top"] / $DATA_LINE_HEIGHT);
	if (!isset($stylesArrByCoord[$line])) {
		$stylesArrByCoord[$line] = array();
	}
	$stylesArrByCoord[$line][] = $styles;
	$surah = intval(explode(":", $ref)[0]);
	$ayah = intval(explode(":", $ref)[1]) + 1; // because we are doing a differnt way
	$ayahRef[] = "$surah-$ayah";
}

$line = 0;
foreach ($stylesArr as &$style) {
	$line++;

	if (isset($stylesArrByCoord[$line][0])) {
		$length = 408 - $stylesArrByCoord[$line][0]["left"];
	} else {
		$length = 360;
	}
	$style["width"] += $length;
	$style["left"] -= $length;
}
$line = 0;
$prevLine = 0;
foreach ($stylesArrByCoord as &$lines) {
	$line++;
	$styleIdx = 0;
	foreach ($lines as &$style) {
		$styleIdx++;
		if (isset($stylesArrByCoord[$line][$styleIdx])) {
			if (count($stylesArrByCoord[$line]) > 2) {
				$length = 0;
				if (isset($stylesArrByCoord[$line][$styleIdx + 1])) {
					$length = $stylesArrByCoord[$line][$styleIdx + 1]["left"] - $stylesArrByCoord[$line][$styleIdx]["left"];
				}
				if (isset($stylesArrByCoord[$line][$styleIdx - 1])) {
					$length = $stylesArrByCoord[$line][$styleIdx - 1]["left"] - $stylesArrByCoord[$line][$styleIdx]["left"];
				}
			} else if (count($stylesArrByCoord[$line]) == 2) {
				$length = 0;
				if (isset($stylesArrByCoord[$line][$styleIdx - 1])) {
					$length = $stylesArrByCoord[$line][$styleIdx - 1]["left"] - $stylesArrByCoord[$line][$styleIdx]["left"];
				}
			} else {
				$length = 408 - $stylesArrByCoord[$line][$styleIdx]["left"];
			}
			$style["width"] += $length;
			$style["left"] -= $length;
		} else {
			$style["width"] = $style["left"] - $MARGIN_LEFT;
			$style["left"] = 48;
		}
		if ($style["left"] < 48) {
			$style["left"] = 48;
		}
	}
	$prevLine = $line;
}

foreach ($stylesArr as $styles) {
	$style = str_replace("=", ":", urldecode(http_build_query($styles, "", "px;")));
	$anchors[] = "<a href='' style='${style}px;position:absolute;background-color:red;opacity:0.3'></a>";
}
$i = 0;
foreach ($stylesArrByCoord as $lines) {
	foreach ($lines as $styles) {
		$ref = $ayahRef[$i];
		$style = str_replace("=", ":", urldecode(http_build_query($styles, "", "px;")));
		$anchors[] = "<a href='' class='ref-$ref' style='${style}px;position:absolute;background-color:blue;opacity:0.3'></a>";
		$i++;
	}
}
$allAnchors = implode("\n", $anchors);

$embeddedCSS = <<<CSS
@media (max-width: 799px) {
	.mdiv {
		width: 45% !important;
	}
	#mushaf-div2 {
		display:none !important;
	}
	.next-page, .prev-page {
		width: 45%;
	}
	#cbo-surah {
		width: 100% !important;
	}
}
.mdiv {
	width: 456px;
	display: inline-block;
	background-repeat: no-repeat;
	background-size: 100%;
	position: relative;
}
.mdiv.left {
	-webkit-box-shadow: 0px 0 0px rgba(0, 0, 0, 0) inset, 0px 0 0px rgba(0,0,0,0.0) inset, 0px 0 0px rgba(0,0,0,0.0) inset, 0px 0 0px rgba(0,0,0,0.3), 0px 0 0px rgba(0,0,0,0.5), -15px 0 15px rgba(0, 0, 0, 0.7) inset;
	-moz-box-shadow: 0px 0 0px rgba(0, 0, 0, 0) inset, 0px 0 0px rgba(0,0,0,0.0) inset, 0px 0 0px rgba(0,0,0,0.0) inset, 0px 0 0px rgba(0,0,0,0.3), 0px 0 0px rgba(0,0,0,0.5), -15px 0 15px rgba(0, 0, 0, 0.7) inset;
	box-shadow: 0px 0 0px rgba(0, 0, 0, 0) inset, 0px 0 0px rgba(0,0,0,0.0) inset, 0px 0 0px rgba(0,0,0,0.0) inset, 0px 0 0px rgba(0,0,0,0.3), 0px 0 0px rgba(0,0,0,0.5), -15px 0 15px rgba(0, 0, 0, 0.7) inset;
  border-left: 7px solid #594E49;
  border-bottom: 3px solid #594E49;
}
.mdiv.right {
	-webkit-box-shadow: 0px 0 0px rgba(0,0,0,0.0) inset, 0px 0 0px rgba(0,0,0,0.4) inset, 0px 0 8px rgba(0,0,0,0.4) inset, 0px 0 4px rgba(0,0,0,0.3), 0px 0 0px rgba(0,0,0,0.5), 15px 0 15px rgba(0, 0, 0, 0.7) inset;
	-mox-box-shadow: 0px 0 0px rgba(0,0,0,0.0) inset, 0px 0 0px rgba(0,0,0,0.4) inset, 0px 0 8px rgba(0,0,0,0.4) inset, 0px 0 4px rgba(0,0,0,0.3), 0px 0 0px rgba(0,0,0,0.5), 15px 0 15px rgba(0, 0, 0, 0.7) inset;
	box-shadow: 0px 0 0px rgba(0,0,0,0.0) inset, 0px 0 0px rgba(0,0,0,0.4) inset, 0px 0 8px rgba(0,0,0,0.4) inset, 0px 0 4px rgba(0,0,0,0.3), 0px 0 0px rgba(0,0,0,0.5), 15px 0 15px rgba(0, 0, 0, 0.7) inset;
  border-right: 7px solid #594E49;
  border-bottom: 3px solid #594E49;
  left: -5px;
}
.mushaf-page {
	vertical-align: text-top;
	width: 100%;
}
.next-page, .prev-page {
	font-size: 1.5em;
	border: 1px solid #454444;
	background: transparent;
    color: #454444;
	min-height: 1em;
}
.next-page:hover, .prev-page:hover {
	border: 1px solid;
    background-color: transparent;
}
CSS;
$embeddedJS = <<<JS
	$pageDataJs
	$pageDataJs2
	var selectedAyah = $ayah;
	var mobileCompliantPage = $mushafPage;
	var origCurrentPage = $origCurrentPage;
	var currentPage = $currentPage;
	var prevPage = $prevPage;
	var nextPage = $nextPage;
	var pageDataAyah = function(idx, pageData) { return pageData[idx].split(",")[0].replace(":", "-"); }
	var ORIG_W = 456;
	var ORIG_H = 691;
	var pageDataX = function(idx, pageData) { return parseInt(pageData[idx].split(",")[1]) + ((pageWidth() - ORIG_W) / 2); }
	var pageDataY = function(idx, pageData) { return parseInt(pageData[idx].split(",")[2]) + ((pageHeight() - ORIG_H) / 2); }
	var marks = function() {}
	var within = function() {}
	var commonCSS = function() {}
	var determineWidth = function() {}
	var ayahHasWordsOnPreviousLine = function() {}
	var pageWidth = function() {}
	var pageHeight = function() {}
	var lineHeight = function() {};
	var edge = function() {};
	var START_OF_PAGE = 39;
	onloads[onloads.length++] = function() {
		if (ScreenSize.medium()) {
			$("#mushaf-div").addClass("$pageSideClass");
			nextPage = origCurrentPage + 1;
			prevPage = origCurrentPage - 1;
			if (origCurrentPage % 2 !== 0) {
				mobileCompliantPage--;
				$("#mushaf-div").removeClass("left").addClass("right").css("border","0");
			} else {
				$("#mushaf-div").removeClass("right").addClass("left").css("border","0");
			}
		}
		pageWidth = function() {return $("#mushaf-div").width();}
		pageHeight = function() {return $("#mushaf-div").height();}
	    lineHeight = function() { return /*pageHeight() * 0.0453;*/ 30 }
		edge = function() { return pageWidth() * 0.1053; }
		// x - y are within z
		within = function (x, y, z) {
			return Math.abs(x-y) <= z;
		}
		determineWidth = function (idx, pageData) {
		    var x = pageDataX(idx, pageData);
		    var y = pageDataY(idx, pageData);
			var prevY = pageData[idx - 1] == undefined ? -1 : pageDataY(idx - 1, pageData);
			var prevX = pageData[idx - 1] == undefined ? -1 : pageDataX(idx - 1, pageData);
			var nextY = pageData[idx + 1] == undefined ? -1 : pageDataY(idx + 1, pageData);
			var nextX = pageData[idx + 1] == undefined ? -1 : pageDataX(idx + 1, pageData);
			var w = (pageWidth() - parseInt(x)) - edge()
			if (within(prevY, y, lineHeight() / 2)) {
				// Next ayah is on same line
				// width is until x of next ayah
				w = prevX - x;
			}
		    return w;
		}
		commonCSS = function (idx, pageData) {
			return {
				"position" : "absolute",
				"top" : pageDataY(idx, pageData) + "px",// - (lineHeight() / 2) + "px",
				"left" : pageDataX(idx, pageData) + "px",
				"width" : determineWidth(idx, pageData) + "px",
				"height" : lineHeight() + "px",
				"opacity" : "0.3",
				"background-color" : "",
				"text-decoration" : "none",
				"cursor" : "default",
				"display": "block"
			}
		}
	    ayahHasWordsOnPreviousLine = function(idx, pageData) {
			var y = pageDataY(idx, pageData);
			var prevY = pageData[idx - 1] == undefined ? -1 : pageDataY(idx - 1, pageData);
			var prevX = pageData[idx - 1] == undefined ? -1 : pageDataX(idx - 1, pageData);
			return (prevX > 60 || prevX == -1) && !within(y, prevY, lineHeight() / 2);
		}
		marks = function(pageData, pageDivSelector) {
			if ($(window).width() < 800) {
				return;
			}
			$(pageDivSelector + " .ayah-sel").remove();
			for (i = 0; i < pageData.length; ++i) {
				var ayahNumber = parseInt(pageDataAyah(i, pageData).split("-")[1]);
			    	var newDiv = $("<a>").css(commonCSS(i, pageData))
					.attr("id", "mark-" + pageDataAyah(i, pageData) + "-e")
					.html("")
					.prependTo(pageDivSelector)
					.addClass("ayah-sel aya-" + pageDataAyah(i, pageData) + " transition")
					.attr("href", "/" + pageDataAyah(i, pageData).replace("-", "#"));
				if (i > 0 && ayahNumber == 1) {
					continue;
				}
				var x = pageDataX(i, pageData);
				var y = pageDataY(i, pageData);
				var prevY = pageData[i - 1] == undefined ? -1 : pageDataY(i - 1, pageData);
				var prevX = pageData[i - 1] == undefined ? -1 : pageDataX(i - 1, pageData);
				var nextY = pageData[i + 1] == undefined ? -1 : pageDataY(i + 1, pageData);
				var nextX = pageData[i + 1] == undefined ? -1 : pageDataX(i + 1, pageData);
				if (ayahHasWordsOnPreviousLine(i, pageData)) {
					var newY = y;
					var j = 0;
					while (newY > Math.max(prevY, START_OF_PAGE)) {
						var newWidth = prevX == -1 ? pageWidth() - (edge() * 2) : prevX - edge();
						if (!within(newY, prevY, lineHeight())) {
							newWidth = pageWidth() - (edge() * 2);
						}
						var newestDiv = $("<a>").css(commonCSS(i, pageData))
						.css(
							{
								"top" : newY - lineHeight()  + "px",
								"left" : edge() + "px",
								"width" : newWidth + "px"
							}
						)
						.attr("id", "mark-" + pageDataAyah(i, pageData) + "-" + j++)
						.html("")
						.prependTo(pageDivSelector)
						.addClass("ayah-sel aya-" + pageDataAyah(i, pageData) + " transition")
						.attr("href", "/" + pageDataAyah(i, pageData).replace("-", "#"));
						newY -= lineHeight();
					}
				} else {
					// This ayah ends at the end of line
				}
			}
			$(".ayah-sel").mousemove(function(e) {
				var ref = $(this).attr("class").split(" ")[1];
				var ayah = $("." + ref);
				ayah.css("background-color", "#000");
			});
			$(".ayah-sel").mouseout(function(e) {
				var ref = $(this).attr("class").split(" ")[1];
				var ayah = $("." + ref);
				ayah.css("background-color", "");
			});

			if ($highlightAyahJS && selectedAyah > 1) {
				$(".ayah-sel.aya-$surah-" + selectedAyah).mousemove();
			}
		}
		var mushafPage = document.getElementById('mushaf-page');
		var mushafPage2 = document.getElementById('mushaf-page2');
		$('#mushaf-page').attr('src', 'https://cdn.amrayn.com/mushaf/medinah2/' + mobileCompliantPage + '.png?v=$config->IMG_VERSION');
		$('#mushaf-page2').attr('src', 'https://cdn.amrayn.com/mushaf/medinah2/$mushafPage2.png?v=$config->IMG_VERSION');
		$('#mushaf-div').css('background-image', 'url(https://cdn.amrayn.com/mushaf/medinah2/' + mobileCompliantPage + '.png?v=$config->IMG_VERSION)');
		$('#mushaf-div2').css('background-image', 'url(https://cdn.amrayn.com/mushaf/medinah2/$mushafPage2.png?v=$config->IMG_VERSION)');
		//mushafPage.onload = function () { marks(pageDataLeft, "#mushaf-div"); }
		//mushafPage2.onload = function () { marks(pageDataRight, "#mushaf-div2"); }


		$(".prev-page").click(function() {
			Utils.fastPageLoad('/quran/p' + prevPage);
		});

		$(".next-page").click(function() {
			Utils.fastPageLoad('/quran/p' + nextPage);
		});

		surahChanged = function() {
			Utils.fastPageLoad('/quran/' + $("#cbo-surah").val());
			Utils.updateSelectedBreadcrumb(CHAPTER_NAMES_ENGLISH[$("#cbo-surah").val()]);
		}
	}
JS;

$socialMediaContents = includeContents("pages/embedded/contents/social-media-share.php");
$contents = <<<PAGE
	<style type='text/css'>
		$embeddedCSS
	</style>
	<script type="text/javascript">
		$embeddedJS
	</script><br/>
	<div style='text-align:center;'>
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
	<br class='only-on-small-screen'/>
	<div class='center-on-small-devices center-on-medium-devices' style='text-align: right'>
		$socialMediaContents
	</div>
	<h1 class='sequential arabic script-uthmani' style='text-align:center;font-size:2em;border:0px;'><?php echo $arabicName; ?></h1>
	<br/>
	<div class='sequential' style='text-align:center;'>
		<a href='/$surah#$ayah' class='transition'>Open in Web App</a> | <a href='/quran/intro/$surah' class='local'>Intro</a> | <a href='/memorization' class='local'>Open Memorization Tracker</a>
	</div>
	<br/>
	<div style='text-align:center'>
		<button class='next-page fa fa-arrow-left'></button>
		&nbsp;&nbsp;&nbsp;
		<button class='prev-page fa fa-arrow-right'></button>
	</div>
	<br/>
	<div class='sequential' style='text-align:center'>
		<div class='mdiv left' id='mushaf-div'>
			<img class='mushaf-page' id='mushaf-page' alt='mushaf-page' style='visibility:hidden'/>
		</div>
		<div class='mdiv right' id='mushaf-div2'>
			$allAnchors
			<img class='mushaf-page' id='mushaf-page2' alt='mushaf-page2' style='visibility:hidden'/>
		</div>
	</div>
	<br/>
	<div style='text-align:center'>
		<button class='next-page fa fa-arrow-left'></button>
		&nbsp;&nbsp;&nbsp;
		<button class='prev-page fa fa-arrow-right'></button>
	</div>
PAGE;
// -------------------------------------------------------------------------------------
init($contents, array(
	"title" => "$surah. $englishName - E-Mushaf",
	"context" => Context::Quran,
	"meta_description" => "$surah. $englishName in mushaf images provided by King Fahd Complex",
	"breadcrumbs" => array("Home" => "/", "Quran" => "/", "E-Mushaf" => "/quran/1", "$englishName" => "/quran/$surah")
));
?>
