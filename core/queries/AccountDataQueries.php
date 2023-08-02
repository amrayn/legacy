<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("core/queries/Queries.php");
includeOnce("core/models/AccountData.php");
includeOnce("core/utils/StringUtils.php");

class AccountDataQueries extends Queries
{
    public static function queryByUserId($userId)
    {
        $userId = StringUtils::cleanAlphaNumber($userId);
        return static::queryActiveByFields(array("user_id" => $userId));
    }

    public static function queryByUserIdAndName($userId, $name)
    {
        $userId = StringUtils::cleanAlphaNumber($userId);
        $name = StringUtils::cleanAlphaNumber($name, array("_"));
        return static::queryActiveByFields(array("user_id" => $userId, "name" => $name), true);
    }

    public static function queryFavouriteHadith($hadithId = null)
    {
        $sql = "SELECT * FROM AccountData where `name` = 'fav_had'";
        if ($hadithId !== null) {
            $hadithId = StringUtils::cleanNumber($hadithId);
            $sql .= " and `value` like '%\"$hadithId\"%'";
        }
        return static::queryBySql($sql);
    }
}
