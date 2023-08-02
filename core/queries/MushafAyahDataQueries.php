<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("core/queries/Queries.php");
includeOnce("core/models/MushafAyahData.php");

class MushafAyahDataQueries extends Queries
{
    public static function queryByTypeAndSurah($type, $surah)
    {
        $type = StringUtils::cleanNumber($type);
        $surah = StringUtils::cleanNumber($surah);
        return static::queryActiveByFields(array("type" => $type, "surah" => $surah));
    }
    public static function queryByType($type)
    {
        $type = StringUtils::cleanNumber($type);
        return static::queryActiveByFields(array("type" => $type));
    }

    public static function queryPageNumberForSurah($surah, $ayah = 1)
    {
        $surah = StringUtils::cleanNumber($surah);
        $ayah = StringUtils::cleanNumber($ayah);
        return static::queryActiveByFields(array("surah" => $surah, "ayah" => $ayah, "type" => MushafAyahDataTypes::PageNumber));
    }
}
