<?php

include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");

includeOnce("core/models/Hadith.php");
includeOnce("core/models/HadithForReview.php");
includeOnce("core/queries/HadithQueries.php");
includeOnce("core/queries/CrossReferenceQueries.php");
includeOnce("core/queries/HadithForReviewQueries.php");
includeOnce("core/queries/NotificationQueries.php");
includeOnce("core/queries/UserQueries.php");
includeOnce("core/queries/ConfigDataQueries.php");
includeOnce("core/queries/AccountDataQueries.php");
includeOnce("core/utils/DateUtils.php");
includeOnce("core/utils/CacheUtils.php");

header('Content-Type: application/json');
header('Cache-Control: no-cache,no-store,must-revalidate');
header('Expires: 0');
header('Pragma: no-cache');

CacheUtils::ignoreCache();
// Minimum permission to access this service is EditHadith
AccountUtils::verifyAccessToUrl(GeneralUserPermission::EditHadith);

$config = Config::getInstance();
$user = UserQueries::queryByEmail(AccountUtils::currentUser()->email);

if ($user === null) {
	echo json_encode(array("message" => "Access denied", "error" => true));
	die();
}
function issueNotificationsForHadithUpdate($englishHadithId, $name, $link, $ignoreUsers = array()) {
	$count = 0;
	$accountDatas = AccountDataQueries::queryFavouriteHadith($englishHadithId);
	$userIds = array();
	foreach ($accountDatas as $ac) {
		$userIds[] = $ac->userId;
	}
	$notifyUpdatesList = UserQueries::queryByUserIdsAndUserPreference(UserPreference::NotificationsHadithUpdate, $userIds);
	foreach ($notifyUpdatesList as $notifyUser) {
		if (in_array($notifyUser->id, $ignoreUsers)) continue;
		NotificationQueries::issueNotification($notifyUser, "Hadith Updated", "Your favourite hadith <b>$name</b> has been updated", "/$link", NotificationType::HadithUpdate);
		$count++;
	}
	return $count;
}

$review = isset($_POST["decline"]);
$cancel = isset($_POST["cancel"]);

if (($cancel || $review) && isset($_POST["fastIdentifier"])) {
	if ($review && !$user->hasPermission(GeneralUserPermission::ReviewHadith)) {
		die(json_encode(array("message" => "Access denied. Not allowed to review.", "error" => true)));
	}
	$fastId = urldecode($_POST["fastIdentifier"]);
	$id = HadithForReview::getActualId($fastId);
	if ($id == -1) {
		echo json_encode(array("message" => "Invalid proposal ID [$fastId]", "error" => true));
	} else {
		$hadithForReview = HadithForReviewQueries::queryById($id);
		// Someone else is trying to cancel this original user's review
		if ($cancel && ($hadithForReview == null || $hadithForReview->userId != $user->id)) {
			echo json_encode(array("message" => "Access denied", "error" => true));
		} else {
			$editor = UserQueries::queryById($hadithForReview->userId);
			if (($review && $user->hasPermission(GeneralUserPermission::MergeHadith)) // Reviewer has merge permission
				|| ($cancel && $user->hasPermission(GeneralUserPermission::EditHadith))) { // Being cancelled by original author
				HadithForReviewQueries::hardDeleteById($hadithForReview->id);
			} else if ($review) {
				if ($user->id === $editor->id) {
					die(json_encode(array("message" => "You cannot review your own proposal", "error" => true)));
				} else if (AccountUtils::checkJsonArrayAccountData(AccountDataKeys::HadithReviewed, "$hadithForReview->id/-1")) { // User already reviewed it
					die(json_encode(array("message" => "Only one review per user allowed.", "error" => true)));
				} else {
					if (AccountUtils::checkJsonArrayAccountData(AccountDataKeys::HadithReviewed, "$hadithForReview->id/+1")) { // Remove any positive review by this user
						AccountUtils::removeJsonArrayAccountData(AccountDataKeys::HadithReviewed, "$hadithForReview->id/+1");
					}
					// User has at least permission to review as we already checked previously
					$hadithForReview->negativeReviews += 1;
					HadithForReviewQueries::persist($hadithForReview);
					AccountUtils::addJsonArrayAccountData(AccountDataKeys::HadithReviewed, "$hadithForReview->id/-1");
				}
			}

			if ($editor !== null && $review) {
				if ($user->hasPermission(GeneralUserPermission::MergeHadith)) { // User has cancelled / declined proposal
					$dataDeclined = AccountDataQueries::queryByUserIdAndName($editor->id, AccountDataKeys::DeclinedHadithEdits);
					AccountUtils::setAndUpdateAccountData($editor, AccountDataKeys::DeclinedHadithEdits, $dataDeclined === null ? 1 : (intval($dataDeclined->value) + 1));
				}
				$declineMessage = "";
				if (isset($_POST["decline-msg"])) {
					$declineMessage =  " with message: <br>" . htmlentities($_POST["decline-msg"]);
				}
				if ($user->hasPermission(GeneralUserPermission::MergeHadith)) { // User has cancelled / declined proposal
					NotificationQueries::issueNotification($editor, "Hadith Changes Declined", "Your changes to <b>" . $hadithForReview->hadithName() . "</b> have been declined by $user->name [ID: $user->userId]\n$declineMessage", "/" . $hadithForReview->hadithLink(), NotificationType::Other);
					echo json_encode(array("message" => "Successfully declined proposal. User notified!"));
				} else { // User has -1 the review
					NotificationQueries::issueNotification($editor, "Hadith Changes Negative Review", "Your changes to <b>" . $hadithForReview->hadithName() . "</b> got negative review by $user->name [ID: $user->userId]$declineMessage", "/" . $hadithForReview->hadithLink(), NotificationType::Other);
					echo json_encode(array("message" => "Successfully posted negative review. User notified!"));
				}
			} else {
				echo json_encode(array("message" => "Successfully cancelled your proposal"));
			}
		}
	}
	die();
}
if (isset($_POST["review"]) && isset($_POST["fastIdentifier"])) {
	if (!$user->hasPermission(GeneralUserPermission::ReviewHadith)) {
		die(json_encode(array("message" => "Access denied", "error" => true)));
	}
	$fastId = urldecode($_POST["fastIdentifier"]);
	$id = HadithForReview::getActualId($fastId);
	if ($id == -1) {
		die(json_encode(array("message" => "Invalid proposal ID [$fastId]", "error" => true)));
	} else {
		$hadithForReview = HadithForReviewQueries::queryById($id);
		if ($hadithForReview == null) {
			die(json_encode(array("message" => "Proposal already cancelled", "error" => true)));
		} else {
			$hadithRefParts = explode("/", $hadithForReview->hadithRef);
			$collectionId = $hadithRefParts[0];
			$book = 1;
			if ($collectionId != 5 && $collectionId != 8) {
				$book = $hadithRefParts[1];
				$hadith = $hadithRefParts[2];
			} else {
				$hadith = $hadithRefParts[1];
			}
			$hadithFromDatabase = HadithQueries::queryHadithByRef(1, $collectionId, $book, $hadith);
			if ($hadithFromDatabase === null) {
				die(json_encode(array("message" => "Hadith not found!", "error" => true)));
			} else {
				$hadithId = $hadithFromDatabase->id;
				$oldText = $hadithFromDatabase->text;
				$editor = UserQueries::queryById($hadithForReview->userId);
				if ($user->hasPermission(GeneralUserPermission::MergeHadith)) {
					$hadithFromDatabase->lastModifiedBy = $editor->id;
					$hadithFromDatabase->text = $hadithForReview->newText;
					$hadithFromDatabase->references = $hadithForReview->newReferences;
					$hadithFromDatabase->tags = $hadithForReview->newTags;
          $newLinks = json_decode($hadithForReview->newLinks);
          unset($hadithFromDatabase->links);
          if (!empty($hadithFromDatabase->id)) {
            // Delete all older ones
            CrossReferenceQueries::deleteForTypeId(CrossReferenceType::Hadith, $hadithFromDatabase->id);
            foreach ($newLinks as $crossRef) {
                $crossReference = new CrossReference(array(
                    "type" => CrossReferenceType::Hadith,
                    "typeId" => $hadithFromDatabase->id,
                    "link" => $crossRef->l,
                    "text" => $crossRef->t
                ));
                // Insert new ones
                CrossReferenceQueries::persist($crossReference);
            }
          }
					$hadithFromDatabase->gradeFlag = $hadithForReview->newGradeFlag;
					CacheUtils::updateHadithVersion();
					HadithQueries::persist($hadithFromDatabase);
					$hadithFromDatabase = HadithQueries::queryHadithByRef(2, $collectionId, $book, $hadith);
					if ($hadithFromDatabase != null && $hadithFromDatabase->text != $hadithForReview->newArabicText) {
						$hadithFromDatabase->lastModifiedBy = $editor->id;
						$hadithFromDatabase->text = $hadithForReview->newArabicText;
						CacheUtils::updateHadithVersion();
						HadithQueries::persist($hadithFromDatabase);
					}
					// Re-query
					$config = Config::getInstance();
					HadithForReviewQueries::hardDeleteById($hadithForReview->id);
					$hadithFromDatabase = HadithQueries::queryActiveById($hadithFromDatabase->id);

					$dataAccepted = AccountDataQueries::queryByUserIdAndName($editor->id, AccountDataKeys::AcceptedHadithEdits);
					AccountUtils::setAndUpdateAccountData($editor, AccountDataKeys::AcceptedHadithEdits, $dataAccepted === null ? 1 : (intval($dataAccepted->value) + 1));

					NotificationQueries::issueNotification($editor, "Hadith Changes Accepted", "Your changes to <b>" . $hadithForReview->hadithName() . "</b> have been accepted and merged. Jazaak Allah khayran for your efforts. May Allah accept it from us.", "/" . $hadithForReview->hadithLink(), NotificationType::Other);
					$admins = UserQueries::queryByGeneralUserPermission(GeneralUserPermission::SuperUser);
					foreach ($admins as $admin) {
						if ($admin->id == $editor->id || $admin->id == $user->id) continue;
						NotificationQueries::issueNotification($admin, "Hadith Changes Merged by $user->name", "$user->name [ID: $user->userId] merged hadith changes for <b>" . $hadithForReview->hadithName() . "</b>", "/" . $hadithForReview->hadithLink(), NotificationType::Other);
					}
					$notifiedTo = issueNotificationsForHadithUpdate($hadithId, $hadithForReview->hadithName(), $hadithForReview->hadithLink(), array($editor->id));
					echo json_encode(array("message" => "Hadith changes have been merged and live", "new_last_updated" => $hadithFromDatabase->lastUpdated, "new_hadith_version" => $config->HADITH_VERSION, "notifiedTo" => $notifiedTo));
				} else {
					// User has at least review permission as we previously checked
					if ($user->id === $editor->id) {
						die(json_encode(array("message" => "You cannot review your own proposal", "error" => true)));
					} else if (AccountUtils::checkJsonArrayAccountData(AccountDataKeys::HadithReviewed, "$hadithForReview->id/+1")) { // User already reviewed it
						die(json_encode(array("message" => "Only one review per user allowed.", "error" => true)));
					} else {
						if (AccountUtils::checkJsonArrayAccountData(AccountDataKeys::HadithReviewed, "$hadithForReview->id/-1")) { // Remove any negative review by this user
							AccountUtils::removeJsonArrayAccountData(AccountDataKeys::HadithReviewed, "$hadithForReview->id/-1");
						}
						// User has at least permission to review as we already checked previously
						$hadithForReview->positiveReviews += 1;
						HadithForReviewQueries::persist($hadithForReview);
						AccountUtils::addJsonArrayAccountData(AccountDataKeys::HadithReviewed, "$hadithForReview->id/+1");
						NotificationQueries::issueNotification($editor, "Hadith Changes Positive Review", "Your changes to <b>" . $hadithForReview->hadithName() . "</b> got positive review by $user->name [ID: $user->userId]", "/" . $hadithForReview->hadithLink(), NotificationType::Other);
						echo json_encode(array("message" => "Successfully posted positive review. User notified!"));
					}
				}
			}
		}
	}
	die();
}

if (isset($_POST["data"])) {
	$modifiedHadithResponse = $_POST["data"];
	$modifiedHadith = new Hadith();
	// We query by ID for fast access and also helps verify the params are not tampered
	$modifiedHadith->id = Hadith::getActualId($modifiedHadithResponse["fastIdentifier"]);
	if (isset($modifiedHadithResponse["fastIdentifier2"])) {
		$modifiedHadith->arabicId = Hadith::getActualId($modifiedHadithResponse["fastIdentifier2"]);
	} else {
		$modifiedHadith->arabicId = -1;
	}
  $modifiedHadith->text = strip_tags($modifiedHadithResponse["text"], "<sup><footnotes><br><b><i>");
  $modifiedHadith->notes = strip_tags($modifiedHadithResponse["notes"], "<sup><footnotes><br><b><i>");
	$modifiedHadith->gradeFlag = $modifiedHadithResponse["gradeFlag"] === null || strlen($modifiedHadithResponse["gradeFlag"]) == 0 ? null : $modifiedHadithResponse["gradeFlag"];
	$modifiedHadith->links = $modifiedHadithResponse["links"] === null || json_encode($modifiedHadithResponse["links"]) == "\"\"" ? null : json_encode($modifiedHadithResponse["links"]);
	$modifiedHadith->lastUpdated = $modifiedHadithResponse["lastUpdated"];
  $modifiedHadith->tags = strip_tags($modifiedHadithResponse["tags"]);
  $modifiedHadith->volume = strip_tags($modifiedHadithResponse["volume"]);
  $modifiedHadith->book = strip_tags($modifiedHadithResponse["book"]);
  $modifiedHadith->hadith = strip_tags($modifiedHadithResponse["hadith"]);
	$modifiedHadith->references = strip_tags($modifiedHadithResponse["references_data"]); // Do not use 'references' that can be null
	$newArabicText = strip_tags($modifiedHadithResponse["textArabic"], "<sup><footnotes><br><b><i>");
  $newArabicNotes = strip_tags($modifiedHadithResponse["notesArabic"], "<sup><footnotes><br><b><i>");

  $createNewHadith = $modifiedHadith->id === -1;
  if (!$createNewHadith) {
    // new english translation
	   $hadithFromDatabase = HadithQueries::queryActiveById($modifiedHadith->id);
	    if ($hadithFromDatabase === null) {
		  echo json_encode(array("message" => "No hadith found", "error" => true));
		  die();
	 }
 } else {
   $hadithFromDatabase = new Hadith(array(
     "databaseId" => 1,
     "collectionId" => strip_tags($modifiedHadithResponse["collectionId"]),
     "book" => $modifiedHadith->book,
     "hadith" => $modifiedHadith->hadith,
     "volume" => $modifiedHadith->volume,
   ));
 }
	if (!$createNewHadith && $hadithFromDatabase->lastUpdated !== $modifiedHadith->lastUpdated) {
		echo json_encode(array("message" => "Hadith modified since last load. Please reload.", "debug_message" => " [Local: ("
			 . DateUtils::localDateStr($modifiedHadith->lastUpdated, DateUtils::getTimezoneUTC())
			 . "), Remote: (" . DateUtils::localDateStr($hadithFromDatabase->lastUpdated, DateUtils::getTimezoneUTC()) . ") -- $hadithFromDatabase->lastUpdated !== $modifiedHadith->lastUpdated]", "error" => true));
		die();
	}
	if ($hadithFromDatabase == $modifiedHadith) {
		if ($modifiedHadith->arabicId != -1) {
			$hadithFromDatabase = HadithQueries::queryActiveById($modifiedHadith->arabicId);
			if ($hadithFromDatabase == null) {
				echo json_encode(array("message" => "No changes detected", "debug_message" => " [Local: ("
					 . DateUtils::localDateStr($modifiedHadith->lastUpdated, DateUtils::getTimezoneUTC())
					 . "), Remote: (" . DateUtils::localDateStr($hadithFromDatabase->lastUpdated, DateUtils::getTimezoneUTC()) . ")]"));
				die();
			}
		} else {
			echo json_encode(array("message" => "No changes detected", "debug_message" => " [Local: ("
				 . DateUtils::localDateStr($modifiedHadith->lastUpdated, DateUtils::getTimezoneUTC())
				 . "), Remote: (" . DateUtils::localDateStr($hadithFromDatabase->lastUpdated, DateUtils::getTimezoneUTC()) . ")]"));
			die();
		}
	}
  $hadithFromDatabase->text = $modifiedHadith->text;
  $hadithFromDatabase->notes = $modifiedHadith->notes;
	$hadithFromDatabase->lastModifiedBy = $user->id;
	$hadithFromDatabase->gradeFlag = $modifiedHadith->gradeFlag;
	$hadithFromDatabase->links = $modifiedHadith->links;
	$hadithFromDatabase->tags = $modifiedHadith->tags;
	$hadithFromDatabase->references = $modifiedHadith->references;

	// Persist if has merge permissions
	if ($user->hasPermission(GeneralUserPermission::MergeHadith)) {
    if (!empty($hadithFromDatabase->id)) {
      $newLinks = json_decode($hadithFromDatabase->links);
      CrossReferenceQueries::deleteForTypeId(CrossReferenceType::Hadith, $hadithFromDatabase->id);
      foreach ($newLinks as $crossRef) {
          $crossReference = new CrossReference(array(
             "type" => CrossReferenceType::Hadith,
             "typeId" => $hadithFromDatabase->id,
             "link" => $crossRef->l,
             "text" => $crossRef->t
          ));
          // Delete all and insert
          CrossReferenceQueries::persist($crossReference);
      }
      unset($hadithFromDatabase->links);
    }
		HadithQueries::persist($hadithFromDatabase);

    $hadithArabicFromDatabase = null;

		if ($modifiedHadith->arabicId != -1) {
			$hadithArabicFromDatabase = HadithQueries::queryActiveById($modifiedHadith->arabicId);
    } else {
      // create new
      $hadithArabicFromDatabase = new Hadith(array(
        "databaseId" => 2,
        "collectionId" => $hadithFromDatabase->collectionId,
        "book" => $hadithFromDatabase->book,
        "hadith" => $hadithFromDatabase->hadith
      ));
    }

    if ($hadithArabicFromDatabase != null) {
      $hadithArabicFromDatabase->lastModifiedBy = $user->id;
      $updated = false;
      if ($hadithArabicFromDatabase->collectionId != $hadithFromDatabase->collectionId) {
        $hadithArabicFromDatabase->collectionId = $hadithFromDatabase->collectionId;
        $updated = true;
      }
      if ($hadithArabicFromDatabase->volume != $hadithFromDatabase->volume) {
        $hadithArabicFromDatabase->volume = $hadithFromDatabase->volume;
        $updated = true;
      }
      if ($hadithArabicFromDatabase->book != $hadithFromDatabase->book) {
        $hadithArabicFromDatabase->book = $hadithFromDatabase->book;
        $updated = true;
      }
      if ($hadithArabicFromDatabase->hadith != $hadithFromDatabase->hadith) {
        $hadithArabicFromDatabase->hadith = $hadithFromDatabase->hadith;
        $updated = true;
      }
      if ($hadithArabicFromDatabase->text != $newArabicText) {
        $hadithArabicFromDatabase->text = $newArabicText;
        $updated = true;
      }

      if ($hadithArabicFromDatabase->notes != $newArabicNotes) {
        $hadithArabicFromDatabase->notes = $newArabicNotes;
        $updated = true;
      }
      if ($updated) {
        HadithQueries::persist($hadithArabicFromDatabase);
      }
    }

		$dataAccepted = AccountDataQueries::queryByUserIdAndName($user->id, AccountDataKeys::AcceptedHadithEdits);
		AccountUtils::setAndUpdateAccountData($user, AccountDataKeys::AcceptedHadithEdits, $dataAccepted === null || !is_numeric($dataAccepted) ? 1 : intval($dataAccepted) + 1);
		// Re-pull updated obj for last updated so user can continue to make edits
		$hadithFromDatabase = HadithQueries::queryActiveById($modifiedHadith->id);
		CacheUtils::updateHadithVersion();
		// Re-query
		$config = Config::getInstance();
		$notifiedTo = issueNotificationsForHadithUpdate($modifiedHadith->id, $hadithFromDatabase->hadithName(), $hadithFromDatabase->hadithLink(), array($user->id));
		echo json_encode(array("message" => "Hadith changes have been merged and live", "new_last_updated" => $hadithFromDatabase->lastUpdated, "new_hadith_version" => $config->HADITH_VERSION, "notifiedTo" => $notifiedTo));
	} else {
		// Handle situation - this will require user to submit his changes for review
		// and add it to his history so he can cancel his request or make further cahnges
		$hadithRef = $hadithFromDatabase->referenceLinkNumbered();
		$existingHadithForReview = HadithForReviewQueries::queryByHadithRef($hadithRef);
		if ($existingHadithForReview == null) {
			$total = HadithForReviewQueries::queryTotal();
			if ($total >= $config->HADITH_FOR_REVIEW_LIMIT) {
				die(json_encode(array("message" => "We have reached our quota. Cannot submit any new reviews until decisions on current submissions has been made.", "error" => true)));
			}
			$hadithForReview = new HadithForReview();
			$hadithForReview->userId = $user->id;
			$hadithForReview->hadithRef = $hadithRef;
			$hadithForReview->newText = $hadithFromDatabase->text;
			$hadithForReview->newReferences = strlen($hadithFromDatabase->references) == 0 ? null : $hadithFromDatabase->references;
			$hadithForReview->newTags = strlen($hadithFromDatabase->tags) == 0 ? null : $hadithFromDatabase->tags;
			$hadithForReview->newGradeFlag = $hadithFromDatabase->gradeFlag;
			$hadithForReview->newLinks = strlen($hadithFromDatabase->links) == 0 ? null : $hadithFromDatabase->links;
			$hadithForReview->newArabicText = $newArabicText;
			HadithForReviewQueries::persist($hadithForReview);
			$reviewers = UserQueries::queryByUserPreferenceAndGeneralUserPermission(UserPreference::NotificationsHadithReview, GeneralUserPermission::ReviewHadith);
			foreach ($reviewers as $reviewer) {
				if ($reviewer->id === $user->id) continue;
				NotificationQueries::issueNotification($reviewer, "Hadith for Review", "Changes to <b>" . $hadithFromDatabase->referenceText() . "</b> has been submitted for review by $user->name [$user->userId]", "/hadith/review?id=" . $hadithForReview->getPublicId(), NotificationType::HadithReviewSubmit);
			}
			$totalReviewers = count($reviewers);
			echo json_encode(array("message" => "Submitted for review [" . $totalReviewers . " reviewer" . ($totalReviewers > 1 ? "s" : "") . " notified]. Please keep a copy of this change with you, if this gets declined, you will lose the changes."));
		} else {
			if ($existingHadithForReview->userId == $user->id) {
				$existingHadithForReview->newText = $hadithFromDatabase->text;
				$existingHadithForReview->newReferences = strlen($hadithFromDatabase->references) == 0 ? null : $hadithFromDatabase->references;
				$existingHadithForReview->newTags = strlen($hadithFromDatabase->tags) == 0 ? null : $hadithFromDatabase->tags;
				$existingHadithForReview->newGradeFlag = $hadithFromDatabase->gradeFlag;
				$existingHadithForReview->newLinks = strlen($hadithFromDatabase->links) == 0 ? null : $hadithFromDatabase->links;
				$existingHadithForReview->newArabicText = $newArabicText;
				HadithForReviewQueries::persist($existingHadithForReview);
				echo json_encode(array("message" => "Your submission is updated. Please keep a copy of this change with you, if this gets declined, you will lose the changes."));
			} else {
				echo json_encode(array("message" => "Hadith already under review. Submitted by another user. Cannot submit another review until decision has been made.", "error" => true));
			}
		}
	}

} else {
	echo json_encode(array("message" => "No data found", "error" => true));
}
?>
