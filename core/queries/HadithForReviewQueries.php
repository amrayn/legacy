<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("core/queries/Queries.php");
includeOnce("core/models/HadithForReview.php");

class HadithForReviewQueries extends Queries
{
    public static function queryByUserAndHadithRef($userId, $hadithRef)
    {
        $userId = StringUtils::cleanNumber($userId);
        $hadithRef = StringUtils::cleanAlphaNumber($hadithRef, array("\/-"));
        return static::queryActiveByFields(array("user_id" => $userId, "hadith_ref" => $hadithRef), true);
    }
    public static function queryByUser($userId)
    {
        $userId = StringUtils::cleanNumber($userId);
        return static::queryActiveByFields(array("user_id" => $userId), false);
    }
    public static function queryByHadithRef($hadithRef)
    {
        $hadithRef = StringUtils::cleanAlphaNumber($hadithRef, array("\/-"));
        return static::queryActiveByFields(array("hadith_ref" => $hadithRef), true);
    }
    public static function queryTotal()
    {
        $sql = "SELECT count(*) as total FROM HadithForReview WHERE status = 1";
        $result = static::querySimpleSql($sql);
        return $result[0]["total"];
    }
}
