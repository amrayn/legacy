<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("core/queries/Queries.php");
includeOnce("core/models/UserPermission.php");

class UserPermissionQueries extends Queries
{
    public static function queryByUser($userId)
    {
        $userId = StringUtils::cleanNumber($userId);
        return static::queryActiveByFields(array("user_id" => $userId));
    }

    public static function queryByUserAndType($userId, $type)
    {
        $userId = StringUtils::cleanNumber($userId);
        $type = StringUtils::cleanNumber($type);
        return static::queryActiveByFields(array("user_id" => $userId, "type" => $type));
    }

    public static function queryPermission($audioId, $userId)
    {
        $audioId = StringUtils::cleanNumber($audioId);
        $userId = StringUtils::cleanNumber($userId);
        $sql = "SELECT * FROM UserPermission WHERE user_id = $userId AND ";
        $sql .= "((type = " . UserPermissionType::EditAudio . " and source_id = $audioId) or ";
        $sql .= "(type = " . UserPermissionType::EditSeries . " and source_id = (select audio_series_id from Audio where id = $audioId)) or ";
        $sql .= "(type = " . UserPermissionType::EditSpeaker . " and source_id = (select speaker_id from Audio where id = $audioId)))";
        $result = static::queryBySql($sql);
        if (count($result) == 0) {
            return null;
        }
        return $result[0];
    }

    public static function queryPermissionByType($sourceId, $type, $userId)
    {
        $sourceId = StringUtils::cleanNumber($sourceId);
        $userId = StringUtils::cleanNumber($userId);
        $type = StringUtils::cleanNumber($type);
        $sql = "SELECT * FROM UserPermission WHERE user_id = $userId AND source_id = $sourceId AND type = $type";
        $result = static::queryBySql($sql);
        if (count($result) == 0) {
            return null;
        }
        return $result[0];
    }

    public static function queryListenPermissionBySource($sourceId)
    {
        $sourceId = StringUtils::cleanNumber($sourceId);
        $type = UserPermissionType::AccessPrivateAudio;
        $sql = "SELECT * FROM UserPermission WHERE source_id = $sourceId AND type = $type";
        return static::queryBySql($sql);
    }

    public static function queryPrivateSeriesPermissionBySource($sourceId)
    {
        return static::queryPermissionsBySourceAndType($sourceId, UserPermissionType::AccessPrivateAudioSeries);
    }

    public static function queryPermissionsBySourceAndType($sourceId, $type)
    {
        $sourceId = StringUtils::cleanNumber($sourceId);
        $type = StringUtils::cleanNumber($type);
        $sql = "SELECT * FROM UserPermission WHERE source_id = $sourceId AND type = $type";
        return static::queryBySql($sql);
    }

    public static function queryBySpeakerAndUser($speakerId, $userId)
    {
        return static::queryPermissionByType($speakerId, UserPermissionType::EditSpeaker, $userId);
    }

    public static function queryBySeriesAndUser($seriesId, $userId)
    {
        $seriesId = StringUtils::cleanNumber($seriesId);
        $userId = StringUtils::cleanNumber($userId);
        $sql = "SELECT * FROM UserPermission WHERE user_id = $userId AND ";
        $sql .= "((type = " . UserPermissionType::EditSeries . " and source_id = $seriesId) or ";
        $sql .= "(type = " . UserPermissionType::EditSpeaker . " and source_id = (select speaker_id from AudioSeries where id = $seriesId)))";
        $result = static::queryBySql($sql);
        if (count($result) == 0) {
            return null;
        }
        return $result[0];
    }
}
