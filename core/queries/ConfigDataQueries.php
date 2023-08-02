<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("core/queries/Queries.php");
includeOnce("core/models/ConfigData.php");

class ConfigDataQueries extends Queries
{
    public static function queryByKey($key)
    {
        return static::queryActiveByFields(array("key" => $key), true);
    }
}
