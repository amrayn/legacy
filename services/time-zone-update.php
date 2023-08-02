<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("core/utils/DateUtils.php");

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

AccountUtils::startSession();

$userOffset = urldecode($_GET["off"]);
$userDaylightsaving = (bool)$_GET["dst"];

$_SESSION["timezone"] = DateUtils::tzOffsetToName($userOffset, $userDaylightsaving);
$_SESSION["timezone_last_update_utc"] = DateUtils::newDateUtc();
$user = AccountUtils::currentUser();
if ($user != null) {
	$timezoneData = AccountDataQueries::queryByUserIdAndName($user->id, AccountDataKeys::TimeZone);
	if ($timezoneData == null) {
		$timezoneData = new AccountData();
		$timezoneData->userId = $user->id;
		$timezoneData->name = AccountDataKeys::TimeZone;
	}
    $timezoneData->value = $_SESSION["timezone"];
    AccountDataQueries::persist($timezoneData);
}
$result = array(
	"offset" => intval($userOffset),
	"dst" => (bool)$userDaylightsaving,
	"timezone" => DateUtils::getTimezoneName(),
	"current_timestamp" => DateUtils::currentLocalDateStr()
);

// Determine lat/lng for prayer times 
// ** PRIVACY NOTE **: At this point we have taken user's permission in the browser in most transparent way)
if(!empty($_SERVER['HTTP_CLIENT_IP'])){
    $ip=$_SERVER['HTTP_CLIENT_IP'];
} elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
    $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
} else{
    $ip=$_SERVER['REMOTE_ADDR'];
}
$ipStr = explode(",", $ip);
$ip = $ipStr[0];

$latitude = isset($_GET["lat"]) && is_numeric($_GET["lat"]) ? $_GET["lat"] : 0;
$longitude = isset($_GET["lng"]) && is_numeric($_GET["lng"]) ? $_GET["lng"] : 0;
function updateData($key, $value) {
    global $user;
    if ($user === null) { return; }
    $data = AccountDataQueries::queryByUserIdAndName($user->id, $key);
    if ($data == null) {
        $data = new AccountData();
        $data->userId = $user->id;
        $data->name = $key;
    }
    $data->value = $value;
    AccountDataQueries::persist($data);
}
if ($latitude == 0 || $longitude == 0) {
	$url = "http://freegeoip.net/json/$ip";
	$ch  = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
	$data = curl_exec($ch);
	curl_close($ch);
	if ($data) {
		$location = json_decode($data);
		$latitude = $location->latitude;
		$longitude = $location->longitude;
	}
}
if ($latitude != 0 && $longitude != 0) {

    // Last known well-formed coords
    updateData(AccountDataKeys::Latitude, $latitude);
    updateData(AccountDataKeys::Longitude, $longitude);
}
if ($user !== null) {
    $latData = AccountDataQueries::queryByUserIdAndName($user->id, AccountDataKeys::Latitude);
    $lngData = AccountDataQueries::queryByUserIdAndName($user->id, AccountDataKeys::Longitude);
    $latitude = $latData->value;
    $longitude = $lngData->value;
}
$result["lat"] = $latitude;
$result["lon"] = $longitude;
$_SESSION["timeInfo"] = $result;
echo json_encode($result);
?>
