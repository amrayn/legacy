<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("core/queries/Queries.php");
includeOnce("core/models/Bookmark.php");

class BookmarkQueries extends Queries
{
    public static function queryByUserId($userId)
    {
        $userId = StringUtils::cleanNumber($userId);
        return static::queryByFields(array("user_id" => $userId), false);
    }

    public static function queryByUserAndType($userId, $type, $offset = 0, $limit = 0)
    {
        $userId = StringUtils::cleanNumber($userId);
        $type = StringUtils::cleanNumber($type);
        $offset = StringUtils::cleanNumber($offset);
        $limit = StringUtils::cleanNumber($limit);
        $sql = "SELECT b.* FROM Bookmark b WHERE user_id = ? AND type = ? ORDER BY id DESC";
        if ($limit > 0) {
            $sql .= " LIMIT $limit";
        }
        if ($offset > 0) {
            $sql .= " OFFSET $offset";
        }
        return static::queryBySql($sql, array($userId, $type));
    }

    public static function queryByUserAndTypes($userId, $types)
    {
        $userId = StringUtils::cleanNumber($userId);
        $types = StringUtils::cleanArrayNumber($types);
        if (count($types) == 0) {
            return array();
        }
        $typesCsv = implode(",", $types);
        $whereClause = array(
                "user_id" => $userId,
        );
        $paramsAndFilters = static::keyValueToParamsAndFilters($whereClause);
        $whereClause = implode(" AND ", $paramsAndFilters["filters"]);
        return static::queryBySql(static::selectAll() . " WHERE $whereClause AND type in ($typesCsv) ORDER BY id ASC;", $paramsAndFilters["params"]);
    }

    public static function queryByUserTypeAndSourceId($userId, $type, $sourceId)
    {
        $result = BookmarkQueries::queryByUserTypeAndSourceIds($userId, $type, array($sourceId));
        if (count($result) == 0) {
            return null;
        }
        return $result[0];
    }

    public static function queryByUserTypeAndSourceIds($userId, $type, $sourceIds)
    {
        $userId = StringUtils::cleanNumber($userId);
        $type = StringUtils::cleanNumber($type);
        $sourceIds = StringUtils::cleanArrayNumber($sourceIds);
        if (count($sourceIds) == 0) {
            return array();
        }
        $sourceIdsCsv = implode(",", $sourceIds);
        $whereClause = array(
            "user_id" => $userId,
            "type" => $type);
        $paramsAndFilters = static::keyValueToParamsAndFilters($whereClause);
        $whereClause = implode(" AND ", $paramsAndFilters["filters"]);

        return static::queryBySql(static::selectAll() . " WHERE $whereClause AND source_id in ($sourceIdsCsv) ORDER BY id ASC;", $paramsAndFilters["params"]);
    }
}
