<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("core/queries/Queries.php");
includeOnce("core/models/ScheduledJob.php");

class ScheduledJobQueries extends Queries
{
    public static function queryByName($name)
    {
        $name = StringUtils::cleanAlphaNumber($name, array("_-"));
        return static::queryActiveByFields(array("name" => $name), true);
    }

    public static function queryJobsRunningFor($seconds)
    {
        $name = StringUtils::cleanNumber($seconds);
        $sql = "SELECT * FROM ScheduledJob WHERE status = ? AND state = ? AND UNIX_TIMESTAMP(now()) - UNIX_TIMESTAMP(state_changed) > ?";
        return static::queryBySql($sql, array(1, ScheduledJobStates::Running, $seconds));
    }
}
