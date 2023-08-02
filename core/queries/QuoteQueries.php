<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("core/queries/Queries.php");
includeOnce("core/models/Quote.php");

class QuoteQueries extends Queries
{
    public static function queryRandom($type = 1) // 1 = Quran
    {
        $result = static::queryBySql(static::selectAll() . " WHERE type=? AND status = ? ORDER BY RAND() LIMIT 1;", array($type, 1));
        if (count($result) == 1) {
            return $result[0];
        }
        return null;
    }
    public static function queryRandomByCategory($type = 1, $category = 1) // 1 = Quran, 1 = ANY
    {
        $result = static::queryBySql(static::selectAll() . " WHERE type=? and category = ? AND status = ? ORDER BY RAND() LIMIT 1;", array($type, $category, 1));
        if (count($result) == 1) {
            return $result[0];
        }
        return null;
    }
}
