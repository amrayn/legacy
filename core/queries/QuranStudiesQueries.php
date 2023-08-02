<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("core/queries/Queries.php");
includeOnce("core/models/QuranStudies.php");

class QuranStudiesQueries extends Queries
{
    public static function queryUserId($userId)
    {
        $userId = StringUtils::cleanNumber($userId);
        return static::queryByFields(array("user_id" => $userId), false);
    }
    public static function queryUserIdAndSurah($userId, $surah)
    {
        $userId = StringUtils::cleanNumber($userId);
        $surah = StringUtils::cleanNumber($surah);
        return static::queryByFields(array("user_id" => $userId, "surah" => $surah), true);
    }
}
