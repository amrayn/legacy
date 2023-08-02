<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("core/queries/Queries.php");
includeOnce("core/models/QuranMeta.php");

class QuranMetaQueries extends Queries
{
    public static function queryByType($type)
    {
        $type = StringUtils::cleanNumber($type);
        return static::queryActiveByFields(array("type" => $type));
    }
}
