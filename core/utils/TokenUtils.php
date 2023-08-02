<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("core/utils/StringUtils.php");
includeOnce("core/utils/DateUtils.php");

class TokenUtils
{
	public static function get() 
	{
		return isset($_REQUEST["token"]) ? $_REQUEST["token"] : null;
	}
	
	public static function verify($token, $maxSeconds) 
	{
		return $token !== null && TokenUtils::life($token) <= $maxSeconds;
	}
	
	public static function life($token) 
	{
		if ($token == null) {
			return false;
		}
		$decryptedToken = StringUtils::advancedDecryptText($token);
		if (strlen($decryptedToken) < 7) {
			return false;
		}
		$pureToken = substr($decryptedToken, 6);
		$tokenDate = strtotime($pureToken);
		$timeNow = strtotime(date(DateUtils::defaultDateFormat()));
		return $timeNow - $tokenDate;
	}
	
	public static function verifyAvailableToken($expiry = 1800) // 1800 = 30 minutes - valid for admin
	{
		return TokenUtils::verify(TokenUtils::get(), $expiry);
	}
	
	public static function build() 
	{
		return StringUtils::advancedEncryptText("Token-" . date(DateUtils::defaultDateFormat()));
	}
	
}

?>
