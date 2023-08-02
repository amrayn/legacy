<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("core/queries/Queries.php");
includeOnce("core/models/HadithBookInfo.php");

class HadithBookInfoQueries extends Queries
{
    public static function queryByCollectionIdAndBookNumber($collectionId)
    {
        $collectionId = StringUtils::cleanNumber($collectionId);
        return static::queryBySql("SELECT * FROM HadithBookInfo WHERE collection_id = ? AND status = ? ORDER BY book;", array($collectionId, 1));
    }
    public static function queryByCollectionIdAndBookNumber2($collectionId, $bookNumber)
    {
      $collectionId = StringUtils::cleanNumber($collectionId);
      $bookNumber = StringUtils::cleanNumber($bookNumber);
      return static::queryBySql("SELECT * FROM HadithBookInfo WHERE collection_id = ? AND book = ? AND status = ?;", array($collectionId, $bookNumber, 1));
    }
}
