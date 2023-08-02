<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");

date_default_timezone_set('UTC');

/**
 * When saving date from code. save using
 *     DateUtils::newDateStrUtc();
 * Whenever pulling (regardless whether you saved from phpMyAdmin or code)
 *     $localDate = DateUtils::toLocalDate(DateUtils::newDateUtc($obj->date))
 *     DateUtils::newDateStr($localDate)
 */
class DateUtils 
{

	public static function datediff($a, $b) 
	{
		$t1 = strtotime($a->format('Y-m-d H:i:s'));
		$t2 = strtotime($b->format('Y-m-d H:i:s'));
		return $t2 - $t1;
	}
	public static function daysDiff($a, $b) 
	{
		return round(DateUtils::datediff($a, $b) / 86400);
	}
	public static function secDiff($date) 
	{
		return DateUtils::datediff($date, new DateTime("now", $date->getTimeZone()));
	}
	public static function toLocalDate($date) 
	{
		$date->setTimeZone(DateUtils::getTimezone());
		return $date;
	}
	public static function nicetime($date) 
	{
		if (!($date instanceof DateTime)) {
			$date = DateUtils::newDate($date);
		}
	    $periods         = array("second", "minute", "hour", "day", "week", "month", "year", "decade");
	    $lengths         = array("60","60","24","7","4.35","12","10");
		$difference = DateUtils::secDiff($date);
		$tense = $difference > 0 ? "ago" : "from now";
		if ($difference == 0) {
			return "Just now";
		}
	    $difference = abs($difference);
	    for ($j = 0; $difference >= $lengths[$j] && $j < count($lengths)-1; $j++) {
	        $difference /= $lengths[$j];
	    }
	    $difference = floor($difference);
    
	    if ($difference != 1) {
	        $periods[$j].= "s";
	    }
    
	    return "$difference $periods[$j] {$tense}";
	}
	public static function displayableTime($date, $fullDate = false)
	{
		$localDate = DateUtils::newDate($date);
		$daysDiff = DateUtils::daysDiff($localDate, DateUtils::newDate());
		$thisYear = date('Y');
		$dateYear = $localDate->format('Y');
		$formattedDate =  $localDate->format('g:i a');
		$formattedDateWithDay =  $localDate->format('D \a\t g:i a');
		$formattedDateWithMonth =  $localDate->format('M d' . ($thisYear != $dateYear ? ', Y' : '') .' \a\t g:i a');
		if ($fullDate) {
			return $formattedDateWithMonth;
		}
		switch ($daysDiff) {
			case 0:
				return DateUtils::nicetime($date);
			case 1:
				return "Yesterday at $formattedDate";
			case 2:
			case 3:
			case 4:
			case 5:
			case 6:
				return "$formattedDateWithDay";
			default:
				return "$formattedDateWithMonth";
		}
	}
	public static function dateComparatorDesc($a, $b)
	{
		$t1 = strtotime($a->date->format('Y-m-d H:i:s'));
		$t2 = strtotime($b->date->format('Y-m-d H:i:s'));
		return $t2 - $t1;
	}
	public static function dateComparatorAsc($a, $b)
	{
		$t1 = strtotime($a->date->format('Y-m-d H:i:s'));
		$t2 = strtotime($b->date->format('Y-m-d H:i:s'));
		return $t1 - $t2;
	}
	public static function isSameDay($date1, $date2) 
	{
		return $date1->format('Y-m-d') == $date2->format('Y-m-d');
	}
	public static function defaultDateFormat() 
	{
		return 'Y-m-d H:i:s';
	}
	public static function getTimezoneName() 
	{
		if (isset($_SESSION) && isset($_SESSION["timezone"]) && trim($_SESSION["timezone"]) != ""){
			return $_SESSION["timezone"];
		}
		return "UTC";
	}
	public static function getLastTimezoneUpdate()
	{
		if (isset($_SESSION) && isset($_SESSION["timezone_last_update_utc"])) {
			return $_SESSION["timezone_last_update_utc"];
		}
		return DateUtils::newDateUtc();
	}
	public static function tzOffsetToName($offset, $isDst = null)
	{
	    if ($isDst === null) {
	        $isDst = date('I');
	    }

	    $offset *= 3600;
	    $zone    = timezone_name_from_abbr('', $offset, $isDst);

	    if ($zone === false) {
	        foreach (timezone_abbreviations_list() as $abbr) {
	            foreach ($abbr as $city) {
	                if ((bool)$city['dst'] === (bool)$isDst
	                		&& strlen($city['timezone_id']) > 0
	                		&& $city['offset'] == $offset) {
	                    $zone = $city['timezone_id'];
	                    break;
	                }
	            }

	            if ($zone !== false)
	            {
	                break;
	            }
	        }
	    }

	    return $zone;
	}
	public static function getTimezone() 
	{
		return new DateTimeZone(DateUtils::getTimezoneName());
	}
	public static function getTimezoneUTC() 
	{
		return new DateTimeZone("UTC");
	}
	public static function newDateWithTimezone($dtstr = "", $timezone = "UTC")
	{
		if (!($timezone instanceof DateTimeZone)) {
			$timezone = new DateTimeZone($timezone);
		}
		if ($dtstr == "") {
			$dtstr = "now";
		}
		if ($dtstr instanceof DateTime) {
			return $dtstr->setTimezone($timezone);
		} else {
			return new DateTime($dtstr, $timezone);
		}
	}
	public static function newDate($dtstr = "") 
	{
		return DateUtils::newDateWithTimezone($dtstr, DateUtils::getTimezone());
	}
	public static function newDateUtc($dtstr = "") 
	{
		return DateUtils::newDateWithTimezone($dtstr);
	}
	public static function newDateStr($dtstr = "", $format = null) 
	{
		if ($format === null) {
			$format = DateUtils::defaultDateFormat();
		}
		return DateUtils::newDate($dtstr)->format($format);
	}
	public static function newDateStrUtc($dtstr = "", $format = null) 
	{
		if ($format === null) {
			$format = DateUtils::defaultDateFormat();
		}
		return DateUtils::newDateUtc($dtstr)->format($format);
	}
	private static function getDBTimezoneName() 
	{
		return DateUtils::tzOffsetToName(-7);
	}
	public static function currentLocalDateStr()
	{
		return DateUtils::newDateStr(DateUtils::toLocalDate(DateUtils::newDate()));
	}
	public static function localDateStr($dateStr, $timezone = "UTC")
	{
		return DateUtils::newDateStr(DateUtils::toLocalDate(DateUtils::newDateWithTimezone($dateStr, $timezone)));
	}
	public static function formattedTime($init, $hideHoursIfZero = true)
	{
		$hours = str_pad(floor($init / 3600), 2, "0", STR_PAD_LEFT);
		$minutes = str_pad(floor(($init / 60) % 60), 2, "0", STR_PAD_LEFT);
		$seconds = str_pad($init % 60, 2, "0", STR_PAD_LEFT);
		return $hours == "00" && $hideHoursIfZero ? "$minutes:$seconds" : "$hours:$minutes:$seconds";
	}
	public static function timeToSec($time)
	{
		$parts = explode(":", $time);
		switch (count($parts)) {
		case 1:
			return intval($parts[0]);
		case 2:
			return intval($parts[0]) * 60 + intval($parts[1]);
		case 3:
			return intval($parts[0]) * 60 * 60 + intval($parts[1]) * 60 + intval($parts[2]);
		}
		return 0;
	}
}
?>
