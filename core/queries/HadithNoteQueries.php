<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("core/queries/Queries.php");
includeOnce("core/models/HadithNote.php");

class HadithNoteQueries extends Queries
{
    public static function queryByUserId($userId)
    {
        $userId = StringUtils::cleanNumber($userId);
        return static::queryByFields(array("user_id" => $userId), false);
    }

    public static function queryByUserAndRef($userId, $collectionId, $bookNumber, $hadithNumber)
    {
        $userId = StringUtils::cleanNumber($userId);
        $collectionId = StringUtils::cleanNumber($collectionId);
        return static::queryByFields(array(
            "user_id" => $userId,
            "collection_id" => $collectionId,
            "book_number" => $bookNumber,
            "hadith_number" => $hadithNumber,
        ), true);
    }
}
