<?php 
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("pages/page.php");
includeOnce("core/models/Hadith.php");
includeOnce("core/models/HadithForReview.php");
includeOnce("core/queries/HadithQueries.php");
includeOnce("core/queries/BookmarkQueries.php");
includeOnce("core/queries/HadithForReviewQueries.php");
includeOnce("core/utils/DateUtils.php");
$config = Config::getInstance();

if (isset($_GET["go"])) {
	$parts = explode(":", $_GET["go"]);
	$collectionId = intval($parts[0]);
	$shortName = Hadith::determineShortNameFromCollectionId($collectionId);
	$newLink = "$shortName/";
	if (count($parts) == 4) {
		// part[1] is volume so we ignore it
		$newLink .= "/" . $parts[2];
	} else {
		$newLink .= "/" . $parts[1];
	}
	if (count($parts) == 4) {
		$newLink .= "/" . $parts[3];
	} else if (count($parts) == 3) {
		$newLink .= "/" . $parts[2];
	}
	header("HTTP/1.1 301 Moved Permanently");
	header("Location: $config->DOMAIN/$newLink");
	exit;
}

$contents = <<<CONTENTS
	<style type='text/css'>
	.chap-link-item > span {
		margin-right: 0.5em;
	}
	.chap-link-item {
		font-size: 1.5em !important;
		padding-left: 36px;
		background-position: 0 50%;
	}
	@media(max-width: 820px) {
		.search-bar,.empty-line-for-search-bar { display: none; }

		#body-contents {
			margin-top: 0px;
		}
	}
	.link-info {
	  	font-size: 0.6em;
	}
	</style>
<blockquote class='front-page '>
CONTENTS;
$contents .= includeContents("pages/embedded/contents/random-hadith.php");
//<span class='link-item-icon fa fa-book'></span>
$bookIcon = <<<FRAG
<span class="link-item-icon fa-stack fa-lg" style="font-size: 1em;">
    <i class="fa fa-circle fa-stack-2x"></i>
    <i class="fa fa-book fa-stack-1x fa-inverse"></i>
</span>
FRAG;
$contents .= <<<CONTENTS
</blockquote><br/>
<center>
		<input placeholder='Search Hadith...' type='text' name='search-bar2' id='search-bar2' value='' class=' rounded search  search-bar2 secondary-search-bar' style='float:none !important;outline-width: 0;font-size: 1.3em;border: 1px solid #999;width:76%;box-shadow: 0px 0px 2pt 1pt #ccc;' />
	</center><br/>
<h1>Browse Hadith</h1>
<div class='chap-links  hadith-links-container'>
<div class='list-link-container hadith-links-container'>
	<a class='list-link-item local' href='/bukhari'>
		<div>
			$bookIcon <span class='link-info-title'>Sahih al-Bukhari</span>
		</div>
	</a>
</div>
<div class='list-link-container hadith-links-container'>
	<a class='list-link-item local' href='/muslim'><div>$bookIcon <span class='link-info-title'>Sahih Muslim</span></div></a>
</div>
<div class='list-link-container hadith-links-container'>
	<a class='list-link-item local' href='/malik'><div>$bookIcon <span class='link-info-title'>Muwatta Imam Malik</span></div></a>
</div>
<div class='list-link-container hadith-links-container'>
	<a class='list-link-item local' href='/tirmidhi'><div>$bookIcon <span class='link-info-title'>Jami` at-Tirmidhi</span></div></a>
</div>
<div class='list-link-container hadith-links-container'>
	<a class='list-link-item local' href='/ibnmajah'><div>$bookIcon <span class='link-info-title'>Sunan Ibn Majah</span></div></a>
</div>
<div class='list-link-container hadith-links-container'>
	<a class='list-link-item local' href='/nasai'><div>$bookIcon <span class='link-info-title'>Sunan an-Nasaa'i</span></div></a>
</div>
<div class='list-link-container hadith-links-container'>
	<a class='list-link-item local' href='/abudawood'><div>$bookIcon <span class='link-info-title'>Sunan Abu Dawood</span></div></a>
</div>
<div class='list-link-container hadith-links-container'>
	<a class='list-link-item local' href='/qudsi'><div>$bookIcon <span class='link-info-title'>Hadith Qudsi</span></div></a>
</div>
<div class='list-link-container hadith-links-container'>
	<a class='list-link-item local' href='/nawawi'><div>$bookIcon <span class='link-info-title'>Al-Nawawi 40 Hadiths</span></div></a>
</div>
<div class='list-link-container hadith-links-container'>
	<a class='list-link-item local' href='/riyadussaliheen'><div>$bookIcon <span class='link-info-title'>Riyaad us-Saliheen</span></div></a>
</div>
<div class='list-link-container hadith-links-container'>
	<a class='list-link-item local' href='/shamail'><div>$bookIcon <span class='link-info-title'>Shama'il at-Tirmidhi</span></div></a>
</div>
</div>
CONTENTS;
if (AccountUtils::isLoggedIn()) {
	$user = AccountUtils::currentUser();
	$starIcon = <<<FRAG
<span class="link-item-icon fa-stack fa-lg" style="font-size: 1em;">
    <i class="fa fa-circle fa-stack-2x"></i>
    <i class="fa fa-star fa-stack-1x fa-inverse"></i>
</span>
FRAG;
	// Bookmarks
	$bookmarks = BookmarkQueries::queryByUserAndType($user->id, BookmarkTypes::Hadith);
	if (count($bookmarks) > 0) {
		$contents .= "<h2>Bookmarks</h2><div class='chap-links hadith-links-container'>";
		foreach ($bookmarks as $bookmark) {
			$hadithObj = HadithQueries::queryActiveById($bookmark->sourceId);
			if ($hadithObj !== null) {
				$key = BookmarkTypes::Hadith . urldecode($hadithObj->getPublicId());
				$contents .= "<div class='list-link-container hadith-links-container'>";
				$contents .= "<a class='list-link-item local' href='" . $hadithObj->referenceLink() . "'>";
				$contents .= "<div>";
				$contents .= $starIcon;
				$contents .= " <span class='link-info-title'>" . $bookmark->title == "" || $bookmark->title == null ? $hadithObj->reference() : $bookmark->title . "</span>";
				$contents .= "</div></a><a class='link-small-icon fa bookmarked' style='right:0px' key='$key'></a></div>";
			}
		}
		$contents .= "</div>";
	}
	
	$hasPermissinoToEdit = $user->hasPermission(GeneralUserPermission::EditHadith);
	$hasPermissionToReviewOrMerge = $user->hasPermission(GeneralUserPermission::ReviewHadith) || $user->hasPermission(GeneralUserPermission::MergeHadith);
	
	
	if ($hasPermissinoToEdit && !$hasPermissionToReviewOrMerge) {
		
		// Show submitted hadiths for review because this user does not have
		// merge permission but can submit hadiths for review
		
		$submittedForReview = HadithForReviewQueries::queryByUser($user->id);
		if (count($submittedForReview) > 0) {
			$contents .= "<h2>Submitted Proposals</h2>";
			$contents .= "<div class='chap-links hadith-links-container'>";
		}
		foreach ($submittedForReview as $submit) {
			$contents .= "<div class='list-link-container hadith-links-container'><a class='list-link-item local' href='/" . $submit->hadithLink() . "'><div>";
			$contents .= "<span class='link-item-icon fa fa-pencil'></span><span class='link-info-title'>";
			$contents .= Hadith::referenceFromLink($submit->hadithRef);
			$contents .= "</span></div></a></div>";
		}
		if (count($submittedForReview) > 0) {
			$contents .= "</div>";
		}
	} else if ($hasPermissionToReviewOrMerge) {
		$waitingForReview = HadithForReviewQueries::queryAllActive(false, 10);
		$totalForReview = count($waitingForReview);
		if ($totalForReview > 0) {
			$contents .= "<h2>Waiting For Review</h2>";
			$contents .= "<p>Please review these ahaadiths changes and submit your decision.";
			$contents .= "</p>";
			$contents .= "<div class='chap-links hadith-links-container'>";
		}
		foreach ($waitingForReview as $hadithForReview) {
			$contents .= "<div class='list-link-container hadith-links-container'><a class='list-link-item local' href='/hadith/review?id=" . $hadithForReview->getPublicId() . "'><div>";
			$contents .= "<span class='link-item-icon fa fa-users'></span><span class='link-info-title'>";
			$contents .= Hadith::referenceFromLink($hadithForReview->hadithRef);
			$contents .= "</span><br/><span class='link-info'>Updated " . DateUtils::displayableTime($hadithForReview->localLastUpdated()) . "</span>";
			$contents .= "</div></a></div>";
		}
		if (count($waitingForReview) > 0) {
			$contents .= "</div>";
		}
	}
	
}
// -------------------------------------------------------------------------------------
init($contents, array(
	"title" => "Hadith",
	"context" => Context::Hadith,
	"meta_description" => "Browse and search hadith from complete collections of Sahih Bukhari, Sahih Muslim, Maliks Muwatta, Jami-at Tirmidhi, Sunan an-Nasai, Sunan Ibn Majah, Sunan Abu Dawood, Nawawi 40 Hadiths, Hadith Qudsi and Riyaad us Saliheen",
	"breadcrumbs" => array("Home" => "/", "Hadith" => "/hadith")
));
?>
