<?php

include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");

header('Cache-Control: no-cache,no-store,must-revalidate');
header('Expires: 0');
header('Pragma: no-cache');

$redr = $_GET["redr"];
$expiry = time()+(3600*24*30); // 30 days

if (!empty($_GET["s"])) {
  setcookie("__f1", $_GET["s"], $expiry, "/", "", true, true);
  die("<html><head><meta http-equiv='refresh' content='0; url=$redr'></head><body>Waiting</body></html>");
}
header('Content-Type: application/json');

if (AccountUtils::isLoggedIn()) {
	$user = AccountUtils::currentUser();
	$sessionData = AccountDataQueries::queryByUserIdAndName($user->id, AccountDataKeys::CurrentSession);
	$token = $sessionData->getPublicId();
	die(json_encode(array("token" => $token, "error" => false)));
} else {
	die(json_encode(array("message" => "Not logged in", "error" => true)));
}
?>
