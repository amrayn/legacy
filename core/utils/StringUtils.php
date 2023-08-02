<?php

include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("core/utils/SecurityUtils.php");

class StringUtils
{
	public static function convertNumber($string, $numbStyle, $useQuranFont = true)
	{
	    $uthmani = array('۰', '۱', '۲', '۳', '٤', '۵', '٦', '۷', '۸', '۹');
	    $indopak = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');
	    $hindi = array('०', '१', '२', '३', '४', '५', '६', '७', '८', '९');
		$me = array('٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩');
	    $num = range(0, 9);
	    switch ($numbStyle) {
	    case 1:
	        return ($useQuranFont ? "<span class='script-kingfahd'>" . str_replace($num, $me, $string) : "<span class='script-uthmani'>" . str_replace($num, $uthmani, $string)) . "</span>";
	    case 2:
	        return "<span class='script-indopak-old'>" . str_replace($num, $indopak, $string) . "</span>";
	    case 3:
	        return str_replace($num, $hindi, $string);
	    default:
	        return $string;
	    }
	}
	public static function generateRandomString($length = 10) {
		return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
	}
	public static function startsWith($haystack, $needle)
	{
	    return $needle === "" || strpos($haystack, $needle) === 0;
	}
	public static function endsWith($haystack, $needle)
	{
	    return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
	}
	public static function encryptText($str) {
		return SecurityUtils::publicEncryptText($str);
	}
	public static function decryptText($hash) {
		return SecurityUtils::publicDecryptText($hash);
	}
	public static function advancedEncryptText($str) {
		return SecurityUtils::advancedEncryptText($str);
	}
	public static function advancedDecryptText($hash) {
		return SecurityUtils::advancedDecryptText($hash);
	}
	public static function normalizePublicId($encryptedText) {
		if (strlen($encryptedText) > 6) {
			return substr($encryptedText, -5) . substr($encryptedText, 0, -6);
		}
		return "";
	}
	public static function encryptedTextAsPublicId($encryptedText) {
		if (strlen($encryptedText) > 6) {
			return substr($encryptedText, 5) . substr($encryptedText, 0, 5);
		}
		return "";
	}
	public static function padZero($text, $length = 3)
	{
		return str_pad($text, $length, "0", STR_PAD_LEFT);
	}
	public static function ceiling($number, $significance = 1)
	{
		return (is_numeric($number) && is_numeric($significance) ) ? (ceil($number/$significance)*$significance) : false;
	}
	public static function removeWords($input, $words)
	{
		return preg_replace('/\b('.implode('|',$words).')\b/i','',$input);
	}
	public static function underscoreToCamel( $string, $first_char_caps = false)
	{
	    if( $first_char_caps == true ) {
	        $string[0] = strtoupper($string[0]);
	    }
	    $func = create_function('$c', 'return strtoupper($c[1]);');
	    return preg_replace_callback('/_([a-z])/', $func, $string);
	}
	public static function camelToUnderscore($string, $first_char_caps = false)
	{
	    if( $first_char_caps == true ) {
	        $string[0] = strtolower($string[0]);
	    }
	    $func = create_function('$c', 'return "_" . strtolower($c[0]);');
	    return preg_replace_callback('/([A-Z])/', $func, $string);
	}


	public static function cleanNumber($query)
	{
		return preg_replace("/[^\d]+/", "", $query);
	}

	public static function cleanAlpha($query, $accept = array())
	{
		$moreChars = implode($accept);
		return preg_replace("/[^a-z$moreChars]+/i", "", $query);
	}

	public static function cleanAlphaNumber($query, $accept = array())
	{
		$moreChars = implode($accept);
		return preg_replace("/[^\da-z$moreChars]/i", "", $query);
	}

	public static function cleanAnythingExceptQuotes($query)
	{
		return StringUtils::cleanAlphaNumber($query, array("\,\._ \-\?\|\{\}\[\]\~\!\@\#\$\%\^\&\*\(\)\=\+"));
	}

	public static function cleanArrayNumber($arr)
	{
		return array_filter($arr, function ($v) { return is_numeric($v); });
	}
	public static function isNumericArray(&$arr, $removeIfFalse = false)
	{
		foreach ($arr as $a) {
			if (!is_int($a)) {
				if ($removeIfFalse) {
					unset($a);
				} else {
					return false;
				}
			}
		}
		return true;
	}
	public static function br2nl($string)
	{
    	return preg_replace('/\<br(\s*)?\/?\>/i', "\n", $string);
	}
	public static function toSearchName($name)
	{
		$result = strtolower(str_replace(" ", "-", StringUtils::cleanAlphaNumber($name, array("-", " ", "_"))));
		while (strpos($result, "--") > -1) {
			$result = str_replace("--", "-", $result);
		}
		return $result;
	}
}
?>
