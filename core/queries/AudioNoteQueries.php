<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("core/queries/Queries.php");
includeOnce("core/models/AudioNote.php");

class AudioNoteQueries extends Queries
{
    public static function queryUserId($userId)
    {
        $userId = StringUtils::cleanNumber($userId);
        return static::queryByFields(array("user_id" => $userId), false);
    }


    public static function queryDefaultForAudioId($audioId)
    {
        $audioId = StringUtils::cleanNumber($audioId);
        return static::queryByFields(array("audio_id" => $audioId, "is_default" => 1), true);
    }

    public static function queryByAudioId($audioId)
    {
        $audioId = StringUtils::cleanNumber($audioId);
        return static::queryByFields(array("audio_id" => $audioId));
    }

    public static function queryAudioIdAndUserId($audioId, $userId, $onlyActive = true)
    {
        $userId = StringUtils::cleanNumber($userId);
        $audioId = StringUtils::cleanNumber($audioId);
        $sql = "SELECT * FROM AudioNote WHERE audio_id = $audioId AND user_id = $userId";
        if ($onlyActive) {
            $sql .= " AND status = 1";
        }
        $result = static::queryBySql($sql, array());
        if (count($result) > 0) {
            return $result[0];
        }
        return null;
    }

    public static function queryByAudioIdsAndUserId($audioIds, $userId = null)
    {
        $userId = $userId === null ? null : StringUtils::cleanNumber($userId);
        $audioIds = StringUtils::cleanArrayNumber($audioIds);
        if (count($audioIds) === 0) {
            return array();
        }
        $csv = implode(",", $audioIds);
        $sql = "SELECT * FROM AudioNote WHERE audio_id in ($csv) ";

        if ($userId !== null) {
            $sql .= " AND user_id = $userId";
        } else {
            $sql .= " AND is_default = true";
        }
        return static::queryBySql($sql, array());
    }
}
