<?php

include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("core/queries/HadithNoteQueries.php");
includeOnce("core/utils/UrlUtils.php");
includeOnce("core/utils/CacheUtils.php");

header('Content-Type: application/json');
header('Cache-Control: no-cache,no-store,must-revalidate');
header('Expires: 0');
header('Pragma: no-cache');

$notePublicId = UrlUtils::paramValue("publicId");
$noteId = HadithNote::getActualId($notePublicId);
$remove = isset($_GET["remove"]);
CacheUtils::ignoreCache();
if (AccountUtils::isLoggedIn()) {
	if ($notePublicId !== null && $noteId > -1) {
		$user = AccountUtils::currentUser();
	
	    $note = HadithNoteQueries::queryActiveById($noteId);
	
		if ($note != null && $note->userId == $user->id) {
			if ($remove) {
				HadithNoteQueries::hardDeleteById($note->id);
				die(json_encode(array("message" => "Note successfully removed", "error" => false)));
			}
		} else {
			die(json_encode(array("message" => "You are not authorised to remove this note", "error" => true)));
		}
		die(json_encode(array("message" => "Unknown request", "error" => true)));
	} else {
		die(json_encode(array("message" => "Cannot identify note", "error" => true)));
	}
} else {
	die(json_encode(array("message" => "User not logged in", "error" => true)));
}
?>
