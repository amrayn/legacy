<?php

// DESCRIPTION

include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
// -------------------------------------- INCLUDES --------------------------------------
includeOnce("pages/page.php");
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

    if (isset($_GET["app"])) {
        header("Location: hadith://$collectionName/$bookNumber/$hadithNumber");
        exit;
    }
}
$metaImages = array();
$metaImg = null;
switch ($collectionId) {
  case 1:
    $metaImg = "https://amrayn.com/assets/images/fe/hadith-titles/bukhari.png";
    break;
  case 2:
    $metaImg = "https://amrayn.com/assets/images/fe/hadith-titles/muslim.png";
    break;
  case 3:
    $metaImg = "https://amrayn.com/assets/images/fe/hadith-titles/dawood.png";
    break;
  case 4:
    $metaImg = "https://amrayn.com/assets/images/fe/hadith-titles/malik.png";
    break;
  case 5:
    $metaImg = "https://amrayn.com/assets/images/fe/hadith-titles/qudsi.png";
    break;
  case 6:
    $metaImg = "https://amrayn.com/assets/images/fe/hadith-titles/tirmidhi.png";
    break;
  case 7:
    $metaImg = "https://amrayn.com/assets/images/fe/hadith-titles/ibnmajah.png";
    break;
  case 8:
    $metaImg = "https://amrayn.com/assets/images/fe/hadith-titles/nawawi40.png";
    break;
  case 9:
    $metaImg = "https://amrayn.com/assets/images/fe/hadith-titles/nasai.png";
    break;
  case 10:
    $metaImg = "https://amrayn.com/assets/images/fe/hadith-titles/riyad.png";
    break;
  case 11:
    $metaImg = "https://amrayn.com/assets/images/fe/hadith-titles/shamail.png";
    break;
  default:
    $metaImg = "https://amrayn.com/assets/images/hadith.png";
}
$metaImages[] = $metaImg;
$contents = getJSResources(array("/scripts/hadith.js"));
$_GET["sharing-hadith"] = "true";
$socialMediaShareButtons = includeContents("pages/embedded/contents/social-media-share.php");
$canEditHadith = AccountUtils::verifyAccess(GeneralUserPermission::EditHadith) ? "true" : "false";
$canMergeHadith = AccountUtils::verifyAccess(GeneralUserPermission::MergeHadith) ? "true" : "false";
if (AccountUtils::isLoggedIn()) {
    $user = AccountUtils::currentUser();
    $preferArabicHadith = $user->hasPreference(UserPreference::PreferArabicHadith) ? "true" : "false";
} else {
    $preferArabicHadith = "false";
}
$embeddedJS = <<<JS
	var IS_EMBEDDED = false;
	var HADITH_VERSION = $config->HADITH_VERSION;
	onloads[onloads.length++] = function() {
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


		FB.init({
			appId: '1428515410778708',
			status: true,
			cookie: true,
			xfbml: true
		});
		//$("#fb-share-button").attr("onclick", "javascript:shareHadithOnFacebook();");
		$("#email-share-button").attr("onclick", "");
		$("#email-share-button").click(function() {
			var lis = [];
			$(".hadith-tag").each(function(k,v) {
				lis.push(v.innerHTML);
			});
			lis = lis.join(", ");
			shareViaEmail($("#tab1").text() + " " + newLineForEmail() + "Classification: " + lis + " " + newLineForEmail() + location.href);
		});
	}

	shareHadithOnPinterest = function() {
		// This is specific to hadith only
		Utils.notifyUser({text:"Contacting Pinterest...", timeout: 4000, type:"success"});
		shareOnPinterest($("#tab1").text());
	}


	shareHadithOnFacebook = function() {
		// This is specific to hadith only
		Utils.notifyUser({text:"Contacting Facebook...", timeout: 4000, type:"success"});
		var lis = [];
					$(".hadith-tag").each(function(k,v) {
						lis.push(v.innerHTML);
					});
					lis = lis.join(", ");
					FB.ui(
					   {
						 method: 'feed',
						 name: document.title,
						 link: location.href,
						 picture: "",
						 caption: "Classification: " + lis,
						 description: $("#tab1").text(),
						 message: $("#tab1").text()
					   },
					   function(response) {
						 if (response && response.post_id) {
						   Analytics.share("Facebook", location.href);
						 }
					   }
					 );
	}
User.Permissions.canEditHadith = $canEditHadith;
User.Permissions.canMergeHadith = $canMergeHadith;
User.Preferences.preferArabicHadith = $preferArabicHadith;
JS;
$contents .= "<script src='//connect.facebook.net/en_US/all.js' type='text/javascript' charset='utf-8'></script><div id='fb-root'></div><script>$embeddedJS</script>";
$contents .= <<<PAGE

    <link rel='stylesheet' type='text/css' href='/styles/hadith.css?v=$config->VERSION' />

	<style>
		@media (max-width: 600px) {
			.link-small-icon {
			  font-size: 30px !important;
			  padding-right: 30px !important;
			}
			.small-spinner {
				background-size: 30px !important;
			}
		}
	</style>
	<div class='left-menu' id='left-menu'>
		<div id="right-side-bar" style="display:inline;margin-left:1.5em;float:right" class="quran-control">
			$socialMediaShareButtons
      <div style="text-align: center;margin-top: 10px;">
        <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&amp;hosted_button_id=JAJ4G7NJU93MA&amp;source=url" target="_blank" rel="noreferrer noopener">
        <img src="https://amrayn.github.io/donate.png?v2"></a>
      </div>
		</div>
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
			Book:
			<select name='books' id='books' onchange='bookChanged()'>
			</select>
		</span>

		<span id='span-hadiths' style='display:none'>
			Hadith:
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

	<br/>
	<div id="selected-hadith" style="min-height:4em;font-size: 1.3em;line-height: 1.3em;">
		<div id="results-text" style="">
PAGE;
if ($config->PRE_RENDER_HADITH) {
    $hadithEnglish = $hadith;
    $hadithArabic = HadithQueries::queryHadithByRef(2, $collectionId, $bookNumber, $hadithNumber);

    // Hadith text
    $hadithTitle = Hadith::determineLongNameFromCollectionId($collectionId);
    if ($hadithEnglish !== null && $hadithEnglish->hasBooks()) {
        $hadithTitle .= " Book $hadithEnglish->book";
    }
    if ($hadithEnglish !== null) {
        $hadithTitle .= " Hadith $hadithEnglish->hadith</span>";
    }
    $htmlHadith = <<<H
				<h2 class='hadith-title'>$hadithTitle</h2>
				<ul id='tabs' class='tabs'>
					<li><a name='#tab1' class='en'>English</a></li>
					<li><a name='#tab2' class='ar'>Arabic</a></li>
				</ul>
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

    // References
    $contents .= "<br/><br/><div class='hadith-section-label'>References</div>";
    if ($hadithEnglish !== null && $hadithEnglish->references != null && $hadithEnglish->references != "") {
        $mediumName = Hadith::determineMediumNameFromCollectionId($collectionId);
        $contents .= "&nbsp;&nbsp;&nbsp;&nbsp;<span class='hadith-ref'>$mediumName $hadithEnglish->references</span>";
    }
    if ($hadithEnglish !== null && $hadithEnglish->hasVolumes()) {
        $longName = Hadith::determineLongNameFromCollectionId($collectionId);
        $contents .= "<br>&nbsp;&nbsp;&nbsp;&nbsp;<span class='hadith-ref'>$longName Volume $hadithEnglish->volume,";
        if ($hadithEnglish->hasBooks()) {
            $contents .= " Book $hadithEnglish->book,";
        }
        $contents .= " Hadith $hadithEnglish->hadith</span>";
    }
} else {
    $contents .= "Loading...";
}
$contents .= <<<PAGE
		</div>
	</div>
PAGE;
// ------------------------------------- INITIALIZE ------------------------------------

init($contents, array(
    "title" => $title,
    "meta_description" => $metaDescription,
    "context" => Context::Hadith,
    "meta_images" => $metaImages,
    "meta_keywords" => "$collectionName,$title,$metaKeywords",
    "breadcrumbs" => array("Home" => "/", "Hadith" => "/hadith/", "Collection" => "/hadith/", "Number" => "/hadith/")
));
