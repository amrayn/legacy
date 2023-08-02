<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("core/queries/Queries.php");
includeOnce("core/models/Ayah.php");
includeOnce("core/utils/StringUtils.php");

class AyahQueries extends Queries
{
    public static function queryBySurahStartEndAndDatabase($surah, $start, $end, $databaseId)
    {
        $surah = StringUtils::cleanNumber($surah);
        $start = StringUtils::cleanNumber($start);
        $end = StringUtils::cleanNumber($end);
        $databaseId = StringUtils::cleanNumber($databaseId);
        $params = array();
        $query = "SELECT * FROM Ayah WHERE database_id = ? AND surah = ?";
        $params[] = $databaseId;
        $params[] = $surah;
        if ($start !== null) {
            if ($end === null || $end <= $start) {
                $query .= " AND ayah = ?";
                $params[] = $start;
            } else {
                $query .= " AND (ayah >= ? AND ayah <= ?)";
                $params[] = $start;
                $params[] = $end;
            }
        }
        $query .= " ORDER BY ayah";
        return static::queryBySql($query, $params);
    }
}
