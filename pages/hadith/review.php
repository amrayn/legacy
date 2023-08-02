<?php 
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("pages/page.php");
includeOnce("core/models/Hadith.php");
includeOnce("core/models/HadithForReview.php");
includeOnce("core/queries/HadithQueries.php");
includeOnce("core/queries/HadithForReviewQueries.php");
includeOnce("core/queries/CrossReferenceQueries.php");
includeOnce("core/queries/UserQueries.php");
includeOnce("core/utils/StringUtils.php");
includeOnce("core/utils/DateUtils.php");
includeOnce("core/utils/CacheUtils.php");
CacheUtils::ignoreCache();
$config = Config::getInstance();
AccountUtils::verifyAccessToUrl(GeneralUserPermission::ReviewHadith);
$contents = <<<CONTENTS
	<style>
	.link-info {
	  	font-size: 0.6em;
	}
	.decision-btn {
		font-size: 4em;
		margin: 0.3em;
		background-color: rgba(0,0,0,0);
		border: 1px solid;
		border-radius: 2px;
		width: 75px;
	}
	#depth {
  	  color: black;
	}
	#ok {
  	  color: green;
	}
	#decline {
  	  color: red;
	}
	ins,.ins {
		background-color: #CAFDCA;
	}
	del,.del {
		background-color: #FDC8C8;
	}
	legend2 {
		border: 1px solid;
		width: 20px;
		height: 20px;
		display: inline;
		padding: 4px;
	}
	#diff,#diffArabic,.side-by-side-diff.left,.side-by-side-diff.right,.diff-border {
		border: 1px solid #666;
		border-radius: 5px;
		padding: 6px;
		font-size: 1.1em;
		font-family: Gentium Basic, Times New Roman, Arial, Helvetica, sans-serif, UthmaniScript;
	}
	#diff,#diffArabic {
		margin-top: 1em;
	}
	#arabic-hadith {
		border: 1px solid #666;
		  border-radius: 5px;
		  padding: 6px;
		  margin-top: 1em;
	}
	.side-by-side-diff {
		display: inline-block;
		width: 40%;
		vertical-align: top;
 		 text-align: left;
	}
	.side-by-side-diff.head {
		text-align:center;
	}
	.mergely-column {
	  border: 1px solid #ccc;
	  width: 40%;
	}
	.only-mergly { display:none;}
	</style>
	<h1>Review Hadith</h1>
CONTENTS;
$contents .= getJSResources(array("/scripts/jsdiff.js", "/scripts/hadith.js"));
$contents .= getCSSResources(array("/styles/hadith.css"));
$user = AccountUtils::currentUser();
if (isset($_GET["id"])) {
	$fastId = $_GET["id"];
	$id = HadithForReview::getActualId($fastId);
	if ($id !== -1) {
		$hadithForReview = HadithForReviewQueries::queryActiveById($id);
		if ($hadithForReview === null) {
			$contents .= "User has cancelled the proposal or it's been accepted/declined by other reviewer";
		} else {
			$hadithRefParts = explode("/", $hadithForReview->hadithRef);
			$collectionId = $hadithRefParts[0];
			$book = 1;
			if ($collectionId != 5 && $collectionId != 8) {
				$book = $hadithRefParts[1];
				$hadithNumber = $hadithRefParts[2];
			} else {
				$hadithNumber = $hadithRefParts[1];
			}
			$hadith = HadithQueries::queryHadithByRef(1, $collectionId, $book, $hadithNumber);
			$arabicHadith = HadithQueries::queryHadithByRef(2, $collectionId, $book, $hadithNumber);
			if ($hadith === null) {
				$contents .= "Hadith not found [Key: " . StringUtils::advancedEncryptText("$collectionId, $book, $hadithNumber") . "]!";
			} else {
				$editor = UserQueries::queryById($hadithForReview->userId);
				$hadithLink = $hadithForReview->hadithLink();
				$reviewId = $hadithForReview->getPublicId();
				if (!$user->hasPermission(GeneralUserPermission::MergeHadith)) {
					$contents .= "You can only review this hadith and +1/-1 it (by clicking <i class='fa fa-check'></i> or <i class='fa fa-close'></i> below)<br/>";
				}
				$contents .= "Hadith: <a href='/$hadithLink' class='local'>" . $hadith->referenceText() . "</a><br/>";
				$contents .= "Edited by: $editor->name <span style='color:#999'>(" . ($editor->userId == null ? "Fast Identifier: " . $editor->getPublicId() : $editor->userId) . ")</span><br/>";

				$dataAccepted = AccountDataQueries::queryByUserIdAndName($editor->id, AccountDataKeys::AcceptedHadithEdits);
				$dataAccepted = $dataAccepted === null ? 0 : intval($dataAccepted->value);
				$dataDeclined = AccountDataQueries::queryByUserIdAndName($editor->id, AccountDataKeys::DeclinedHadithEdits);
				$dataDeclined = $dataDeclined === null ? 0 : intval($dataDeclined->value);
				$totalEdits = $dataAccepted + $dataDeclined;
				if ($totalEdits > 0) {
					$contents .= "Acceptance: " . number_format((float)(($dataAccepted / $totalEdits) * 100), 1) . "% ($totalEdits edits)<br/>";
				}
				$contents .= "+1: $hadithForReview->positiveReviews<br/>";
				$contents .= "-1: $hadithForReview->negativeReviews<br/>";

				$contents .= "Last updated: " . DateUtils::displayableTime($hadithForReview->localLastUpdated()) . "<br/><br/>";
				$contents .= "Legend: <legend2 class='ins'> Added</legend2> <legend2 class='del'>Deleted</legend2><br/>";
				$contents .= "<h3>Text</h3><div id='diff'></div><br>";
				$original = nl2br($hadith->text);
				$edited = nl2br($hadithForReview->newText);
				$contents .= "<center><div class='side-by-side-diff head' >Original</div><div class='side-by-side-diff head'>Edited</div>";
				$contents .= "<div class='side-by-side-diff left' id='diff-orig'>$original</div><div class='side-by-side-diff right' id='diff-edited'>$edited</div><br></center>";
				if ($arabicHadith !== null) {
					if ($arabicHadith->text != $hadithForReview->newArabicText) {
						$originalArabic = nl2br($arabicHadith->text);
						$editedArabic = nl2br($hadithForReview->newArabicText);
						$contents .= "<div id='diffArabic' class='arabic hadith-text-arabic' style='font-size:1.8em;'></div><br><br><center><div class='side-by-side-diff head' >Original</div><div class='side-by-side-diff head'>Edited</div>";
						$contents .= "<div class='side-by-side-diff left arabic hadith-text-arabic' style='font-size:1.8em;text-align: right;' id='diff-orig-arabic'>$originalArabic</div><div class='side-by-side-diff right arabic hadith-text-arabic' style='font-size:1.8em;text-align: right;' id='diff-edited-arabic'>$editedArabic</div><br></center>";
					} else {
						$arabicHadithText = nl2br($arabicHadith->text);
						$contents .= "<br><br>Here is arabic version of this hadith (if you want to confirm translation)<div id='arabic-hadith' class='arabic hadith-text-arabic'>$arabicHadithText</div><br/>";
					}
				}
				if ($hadithForReview->newReferences != $hadith->references) {
					$contents .= "<h3>References</h3><div id='diffReferences'></div><br/>";
				}
				if ($hadithForReview->newTags != $hadith->tags) {
					$contents .= "<h3>Tags</h3><div id='diffTags'></div><br/>";
				}
				$contents .= "<div class='grade-flag-container'></div><br/>";
				$gradeFlagChanged = $hadithForReview->newGradeFlag == $hadith->gradeFlag ? "false" : "true";

				$contents .= "<div class='cross-ref-links-container'></div><br/>";
				$crossRefLinksJson = json_encode($hadithForReview->newLinks);
                                $crossReferences = CrossReferenceQueries::queryByTypeId(CrossReferenceType::Hadith, $hadith->id);
                                $crossRefs = array();
                                foreach ($crossReferences as $c) {
                                    $crossRefs[] = array("l" => $c->link, "t" => $c->text);
                                }
				$crossRefLinksJsonOriginal = json_encode(json_encode($crossRefs));
				$crossRefLinksChanged = $crossRefLinksJsonOriginal != $crossRefLinksJson ? "true" : "false";
Debug::log($crossRefLinksJsonOriginal);
				$contents .= "<br/><center><button id='ok' class='decision-btn'><span class='fa fa-check'></span></button><button id='decline' class='decision-btn'><span class='fa fa-close'></span></button></center>";
				$original = json_encode(nl2br($hadith->text));
				$edited = json_encode(nl2br($hadithForReview->newText));
				$contents .= <<<PAGE
				<script>
				$(document).ready(function() {
					if ($gradeFlagChanged) {
						$('.grade-flag-container').html("<h3>Classification</h3>");
						$('.grade-flag-container').append("<br><center><div class='side-by-side-diff head' >Original</div><div class='side-by-side-diff head'>Edited</div><div class='side-by-side-diff left'>" + nl2br(br2nl(buildGradeLabelsHTML($hadith->gradeFlag)).trim()) + "</div><div class='side-by-side-diff right'>" + nl2br(br2nl(buildGradeLabelsHTML($hadithForReview->newGradeFlag)).trim()) + "</div></center>");
					}
					if ($crossRefLinksChanged) {
						$('.cross-ref-links-container').html("<h3>Cross References</h3>");
						$('.cross-ref-links-container').append("<div class='diff-border'>" + diffString(
		   					nl2br(br2nl(buildCrossReferenceHTML(JSON.parse($crossRefLinksJsonOriginal))).trim()), 
							nl2br(br2nl(buildCrossReferenceHTML(JSON.parse($crossRefLinksJson))).trim())
						) + "</div>");
						$('.cross-ref-links-container').append("<br><center><div class='side-by-side-diff head' >Original</div><div class='side-by-side-diff head'>Edited</div><div class='side-by-side-diff left'>" + nl2br(br2nl(buildCrossReferenceHTML(JSON.parse($crossRefLinksJsonOriginal))).trim()) + "</div><div class='side-by-side-diff right'>" + nl2br(br2nl(buildCrossReferenceHTML(JSON.parse($crossRefLinksJson))).trim()) + "</div></center>");
					}
					$('#diff').html(diffString(
	   					$('#diff-orig').text(), $('#diff-edited').text()
					));
					$('#diffArabic').html(diffString(
	   					$('#diff-orig-arabic').text(), $('#diff-edited-arabic').text()
					));
					$('#diffTags').html(diffString(
	   					"$hadith->tags", "$hadithForReview->newTags"
					));
					$('#diffReferences').html(diffString(
	   					"$hadith->references", "$hadithForReview->newReferences"
					));
					$("#ok").click(function() {
						if (confirm("Are you sure you wish to accept this change?")) {
							NProgress.start();
							$.ajax({
								url : '/svc/hadith/edit' + __svcargs,
								data : {
									"fastIdentifier" : "$reviewId",
									"review" : true
								},
								type: 'POST'
							}).always(function(resp) {
								if (resp.error) {
									Utils.notifyMessage({text: resp.message, type: 'error', timeout: 5000 });
								} else {
									Utils.notifyMessage({ text: resp.message, type: 'success', timeout: 5000 });
									Utils.fastPageLoad("/$hadithLink");
								}
								NProgress.done();
							});
						}
					});
					$("#decline").click(function() {
					
						if (confirm("Are you sure you wish to decline this change?")) {
							var declineMessage = "";
							while (declineMessage.length < 10) {
								declineMessage = prompt("Please enter a decline message (10-150 characters)", "Jazaak Allah khayr for your efforts but I cannot accept this change. Please refer to our hadith edit policy on 'About' page.");
							}
							declineMessage = declineMessage.substr(0, 150);
							NProgress.start();
							$.ajax({
								url : '/svc/hadith/edit' + __svcargs,
								data : {
									"fastIdentifier" : "$reviewId",
									"decline" : true,
									"decline-msg" : declineMessage
								},
								type: 'POST'
							}).always(function(resp) {
								if (resp.error) {
									Utils.notifyMessage({text: resp.message, type: 'error', timeout: 5000 });
								} else {
									Utils.notifyMessage({ text: resp.message, type: 'success', timeout: 5000 });
									Utils.fastPageLoad("/$hadithLink");
								}
								NProgress.done();
							});
						}
					});
				});
				</script>
PAGE;
			}
		}
	} else {
		$contents .= "Invalid ID provided!";
	}
} else {
	// List top 10 for review
	$waitingForReview = HadithForReviewQueries::queryAllActive(false, 10);
	if (count($waitingForReview) > 0) {
		$contents .= "<div class='chap-links sequential' style='width:100%;border:0;text-align:center;'>";
	}
	foreach ($waitingForReview as $hadithForReview) {
		$contents .= "<a class='chap-link-item local' href='/hadith/review?id=" . $hadithForReview->getPublicId() . "'>";
		$contents .= "<span class='link-item-icon fa fa-users'></span>";
		$contents .= Hadith::referenceFromLink($hadithForReview->hadithRef);
		$contents .= "<br/><span class='link-info'>Updated " . DateUtils::displayableTime($hadithForReview->localLastUpdated()) . "</span>";
		$contents .= "</a>";
	}
	if (count($waitingForReview) > 0) {
		$contents .= "</div>";
	} else {
		$contents .= "<p>Nothing to review.</p>";
	}
}
// ------------------------------------------------------------------------------------
init($contents, array(
	"title" => "Review Hadith Changes",
	"context" => Context::Hadith,
	"meta_description" => "Review changes submitted by users who can edit hadith"
));
?>
