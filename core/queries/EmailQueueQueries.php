<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("core/queries/Queries.php");
includeOnce("core/models/EmailQueue.php");
includeOnce("core/models/AccountBlackList.php");

class EmailQueueQueries extends Queries
{
    public static function queryUnsent()
    {
        return static::queryBySql("SELECT * FROM EmailQueue WHERE sent_date IS NULL AND status = ? ORDER BY priority, date_added LIMIT 100;", array(1));
    }

    public static function deleteSentEmails($maxDays = 3)
    {
        return static::queryBySql("DELETE FROM EmailQueue WHERE sent_date IS NOT NULL AND DATEDIFF(CURRENT_DATE, sent_date) > ?);", array($maxDays));
    }

    public static function queryRecentByType($type, $email, $maxSeconds = 240)
    {
        $type = StringUtils::cleanNumber($type);
        return static::queryBySql("SELECT * FROM EmailQueue WHERE to_address = ? AND type = ? AND status = ? AND (TIME_TO_SEC(TIMEDIFF(now(), date_added)) < ? OR sent_date IS NULL) LIMIT 1;", array($email, $type, 1, $maxSeconds));
    }
}
