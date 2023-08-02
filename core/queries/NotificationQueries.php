<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("core/queries/Queries.php");
includeOnce("core/models/Notification.php");
includeOnce("core/models/User.php");
includeOnce("core/utils/DateUtils.php");

class NotificationQueries extends Queries
{
    public static function housekeeping()
    {
        $config = Config::getInstance();
        $housekeepingThreshold = $config->NOTIFICATION_HOUSEKEEPING_THRESHOLD; // in days
        $oldDate = DateUtils::newDateUtc()->sub(new DateInterval('P' . $housekeepingThreshold . 'D'))->format(DateUtils::defaultDateFormat());
        $sql = "DELETE FROM Notification WHERE date <= '$oldDate' AND seen = true;";
        static::executeSimple($sql);
        $housekeepingThresholdUnread = $config->NOTIFICATION_HOUSEKEEPING_THRESHOLD_UNREAD; // in days
        $oldDate = DateUtils::newDateUtc()->sub(new DateInterval('P' . $housekeepingThresholdUnread . 'D'))->format(DateUtils::defaultDateFormat());
        $sql = "DELETE FROM Notification WHERE date <= '$oldDate' AND seen = false;";
        static::executeSimple($sql);
    }

    public static function markAllRead($userId)
    {
        $userId = StringUtils::cleanAlphaNumber($userId);
        $sql = "UPDATE Notification SET seen = true WHERE user_id = $userId AND seen = false;";
        return static::executeSimple($sql);
    }

    public static function removeAllRead($userId)
    {
        $userId = StringUtils::cleanAlphaNumber($userId);
        $sql = "DELETE FROM Notification WHERE user_id = $userId AND seen = true;";
        return static::executeSimple($sql);
    }

    public static function queryUnreadByIdAndUserId($id, $userId)
    {
        $userId = StringUtils::cleanAlphaNumber($userId);
        $id = StringUtils::cleanAlphaNumber($id);
        $keyValue = array("id" => $id, "user_id" => $userId);
        return static::queryByFields($keyValue, true);
    }

    public static function queryUnreadByUserId($userId, $limit = 20)
    {
        $userId = StringUtils::cleanAlphaNumber($userId);
        $limit = StringUtils::cleanAlphaNumber($limit);
        $keyValue = array("user_id" => $userId, "seen" => "0");
        $paramsAndFilters = static::keyValueToParamsAndFilters($keyValue);
        $whereClause = implode(" AND ", $paramsAndFilters["filters"]);

        $result = static::queryBySql(static::selectAll() . " WHERE $whereClause ORDER BY date DESC, seen DESC LIMIT $limit;", $paramsAndFilters["params"]);
        return $result;
    }

    public static function queryUnreadCountByUserId($userId)
    {
        $userId = StringUtils::cleanAlphaNumber($userId);
        $limit = StringUtils::cleanAlphaNumber($limit);
        $keyValue = array("user_id" => $userId, "seen" => "0");
        $paramsAndFilters = static::keyValueToParamsAndFilters($keyValue);
        $whereClause = implode(" AND ", $paramsAndFilters["filters"]);

        $result = static::queryBySql("SELECT count(*) as total FROM Notification WHERE $whereClause;", $paramsAndFilters["params"]);
        return $result;
    }

    public static function queryReadCountByUserId($userId)
    {
        $userId = StringUtils::cleanAlphaNumber($userId);
        $limit = StringUtils::cleanAlphaNumber($limit);
        $keyValue = array("user_id" => $userId, "seen" => "1");
        $paramsAndFilters = static::keyValueToParamsAndFilters($keyValue);
        $whereClause = implode(" AND ", $paramsAndFilters["filters"]);

        $result = static::queryBySql("SELECT count(*) as total FROM Notification WHERE $whereClause;", $paramsAndFilters["params"]);
        return $result;
    }

    public static function queryReadByUserId($userId, $limit = 20)
    {
        $userId = StringUtils::cleanAlphaNumber($userId);
        $limit = StringUtils::cleanAlphaNumber($limit);
        $keyValue = array("user_id" => $userId, "seen" => "1");
        $paramsAndFilters = static::keyValueToParamsAndFilters($keyValue);
        $whereClause = implode(" AND ", $paramsAndFilters["filters"]);

        $result = static::queryBySql(static::selectAll() . " WHERE $whereClause ORDER BY date DESC, seen DESC LIMIT $limit;", $paramsAndFilters["params"]);
        return $result;
    }

    public static function issueNotification($user, $title, $details, $link, $type = NotificationType::Other)
    {
        if (NotificationType::checkValidForUser($user, $type)) {
            $newNotification = new Notification();
            $newNotification->date = DateUtils::newDateStrUtc();
            $newNotification->userId = $user->id;
            $newNotification->title = $title;
            $newNotification->details = $details;
            $newNotification->link = $link;
            $typeFlag = NotificationType::getFlag($type);
            $newNotification->type = $typeFlag;
            return NotificationQueries::persist($newNotification);
        }
        return null;
    }
}
