<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("core/queries/Queries.php");
includeOnce("core/models/AudioNoteItem.php");

class AudioNoteItemQueries extends Queries
{
    public static function queryAudioNoteId($audioNoteId)
    {
        $audioNoteId = StringUtils::cleanNumber($audioNoteId);
        $sql = "SELECT * FROM AudioNoteItem WHERE audio_note_id = $audioNoteId AND status = 1 ORDER BY sort_order, id, heading";
        return static::queryBySql($sql, array());
    }

    public static function hardDeleteByAudioNoteId($audioNoteId)
    {
        $audioNoteId = StringUtils::cleanNumber($audioNoteId);
        $sql = "DELETE FROM AudioNoteItem WHERE audio_note_id = $audioNoteId";
        return static::executeSimple($sql, array());
    }

    public static function queryAudioNoteIdByIdsExcept($audioNoteId, $excludeIds)
    {
        $audioNoteId = StringUtils::cleanNumber($audioNoteId);
        $excludeIds = StringUtils::cleanArrayNumber($excludeIds);
        $csv = implode(",", $excludeIds);
        $sql = "SELECT * FROM AudioNoteItem WHERE audio_note_id = $audioNoteId AND id NOT IN ($csv) AND status = 1";
        return static::queryBySql($sql, array());
    }
}
