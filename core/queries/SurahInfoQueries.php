<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("core/queries/Queries.php");
includeOnce("core/models/SurahInfo.php");

class SurahInfoQueries extends Queries
{
    public static function queryBySurah($surah)
    {
        $surah = StringUtils::cleanNumber($surah);
        return static::queryActiveByFields(array("surah" => $surah));
    }
    public static function queryByTypeAndSurah($type, $surah)
    {
        $type = StringUtils::cleanNumber($type);
        $surah = StringUtils::cleanNumber($surah);
        return static::queryActiveByFields(array("type" => $type, "surah" => $surah), true);
    }
    public static function queryByType($type)
    {
        $type = StringUtils::cleanNumber($type);
        return static::queryActiveByFields(array("type" => $type));
    }
    public static function queryByTypes($types)
    {
        $types = StringUtils::cleanArrayNumber($types);
        $sql = "SELECT * FROM SurahInfo WHERE type in (" . implode(",", $types) . ") AND status = 1 ORDER BY surah";
        return static::queryBySql($sql, array());
    }
}
