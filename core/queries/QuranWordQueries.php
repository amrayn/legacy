<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("core/queries/Queries.php");
includeOnce("core/models/QuranWord.php");

class QuranWordQueries extends Queries
{
    public static function queryBySurahStartEnd($surah, $start, $end)
    {
        $surah = StringUtils::cleanNumber($surah);
        $start = StringUtils::cleanNumber($start);
        $end = StringUtils::cleanNumber($end);
        $params = array();
        $query = "SELECT * FROM QuranWord WHERE database_id = ? AND surah = ?";
        $params[] = 198; // English
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
        $query .= " ORDER BY ayah, word_id";
        return static::queryBySql($query, $params);
    }
}
