<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("core/queries/Queries.php");
includeOnce("core/models/AudioCategory.php");

class AudioCategoryQueries extends Queries
{
    public static function queryBySearchName($searchName)
    {
        $searchName = StringUtils::toSearchName($searchName);
        $list = static::queryBySql("SELECT * FROM AudioCategory WHERE search_name = ? AND status = ? LIMIT 1;", array($searchName, 1));
        if (count($list) > 0) {
            return $list[0];
        }
        return null;
    }

    public static function updateSearchNames()
    {
        static::_updateSearchNames("name", "searchName");
    }
}
