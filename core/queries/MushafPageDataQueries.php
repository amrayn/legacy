<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("core/queries/Queries.php");
includeOnce("core/models/MushafPageData.php");

class MushafPageDataQueries extends Queries
{
    public static function queryByPage($page)
    {
        $page = StringUtils::cleanNumber($page);
        return static::queryActiveByFields(array("page" => $page), true);
    }
}
