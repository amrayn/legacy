<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("core/queries/Queries.php");
includeOnce("core/models/Hadith.php");

class HadithQueries extends Queries
{
    public static function queryHadithByRef($databaseId, $collectionId, $bookNumber, $hadithNumber)
    {
        $collectionId = StringUtils::cleanNumber($collectionId);
        $bookNumber = StringUtils::cleanNumber($bookNumber);
        $hadithNumber = StringUtils::cleanAlphaNumber($hadithNumber, array("."));
        $hadith = static::queryBySql("SELECT * FROM Hadith WHERE database_id = ? AND collection_id = ? AND book = ? AND hadith = ? AND status = ? LIMIT 1;", array($databaseId, $collectionId, $bookNumber, $hadithNumber, 1));
        return count($hadith) == 0 ? null : $hadith[0];
    }

    public static function queryBooks($collectionId)
    {
        $collectionId = StringUtils::cleanNumber($collectionId);
        return static::querySimpleSql("SELECT DISTINCT book FROM Hadith WHERE database_id = ? AND collection_id = ? AND status = ? ORDER BY book;", array(1, $collectionId, 1));
    }

    public static function queryHadiths($collectionId, $bookNumber)
    {
        $collectionId = StringUtils::cleanNumber($collectionId);
        $bookNumber = StringUtils::cleanNumber($bookNumber);
        return static::querySimpleSql("SELECT DISTINCT hadith FROM Hadith WHERE database_id = ? AND collection_id = ? AND book = ? AND status = ? ORDER BY cast(hadith as signed);", array(1, $collectionId, $bookNumber, 1));
    }

    public static function queryHadithsFull($collectionId, $bookNumber)
    {
        $collectionId = StringUtils::cleanNumber($collectionId);
        $bookNumber = StringUtils::cleanNumber($bookNumber);
        return static::queryBySql("SELECT * FROM Hadith WHERE database_id = ? AND collection_id = ? AND book = ? AND status = ? ORDER BY cast(hadith as signed);", array(1, $collectionId, $bookNumber, 1));
    }


    public static function queryByIds($ids)
    {
        if (count($ids) == 0) {
            return array();
        }
        $ids = StringUtils::cleanArrayNumber($ids);
        $csv = implode(",", $ids);
        return static::queryBySql("SELECT * FROM Hadith WHERE id in ($csv) AND status = ? ORDER BY cast(hadith as signed);", array(1));
    }


    public static function queryHadithTopLastModifiedBy($userId, $limit = 10)
    {
        $userId = StringUtils::cleanNumber($userId);
        $limit = StringUtils::cleanNumber($limit);
        $result = static::querySimpleSql("SELECT * FROM Hadith WHERE database_id = ? AND last_modified_by = ? ORDER BY last_updated DESC LIMIT $limit;", array(1, $userId));
        $returnObj = array();
        foreach ($result as $field) {
            $obj = static::newInstance();
            static::setObjectFromQueryResult($field, $obj);
            $returnObj[] = $obj;
        }
        return $returnObj;
    }
}
