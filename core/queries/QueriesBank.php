<?php

include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");

/**
 * This is only for breaking cache
 */
class QueriesBank
{
    const SpeakerQueries_queryAllActiveAndCounts = "SELECT Speaker.*, (SELECT count(*) FROM Audio WHERE speaker_id = Speaker.id AND audio_series_id = 0 AND status = 1 AND is_private = 0) as total_lectures, (SELECT count(*) FROM AudioSeries WHERE speaker_id = Speaker.id AND status = 1 AND is_private = 0) as total_lecture_series, (SELECT count(*) FROM Audio WHERE speaker_id = Speaker.id AND audio_series_id = 0 AND status = 1 AND is_private = 0 AND DATEDIFF(CURRENT_DATE, date_added) < (SELECT `value` FROM ConfigData WHERE `key` = 'LECTURE_NEW_TAG_DAYS')) as new_solo_lectures_count, (SELECT count(*) FROM Audio WHERE speaker_id = Speaker.id AND (SELECT date_added FROM AudioSeries WHERE id = audio_series_id AND DATEDIFF(CURRENT_DATE, date_added) > (SELECT `value` FROM ConfigData WHERE `key` = 'LECTURE_NEW_TAG_DAYS')) AND status = 1 AND is_private = 0 AND DATEDIFF(CURRENT_DATE, date_added) < (SELECT `value` FROM ConfigData WHERE `key` = 'LECTURE_NEW_TAG_DAYS')) as new_lectures_in_series_count, (SELECT count(*) FROM AudioSeries WHERE speaker_id = Speaker.id AND status = 1 AND is_private = 0 AND DATEDIFF(CURRENT_DATE, date_added) <= (SELECT `value` FROM ConfigData WHERE `key` = 'LECTURE_NEW_TAG_DAYS')) as new_lecture_series_count FROM Speaker WHERE status = 1 ORDER BY date_added>>>";
    const SpeakerQueries_queryById = "SELECT * FROM Speaker  WHERE  `id` = ?  ORDER BY id ASC;>>>%s";
    const SpeakerQueries_queryActiveById = "SELECT * FROM Speaker  WHERE  `id` = ?  AND  `status` = ?  ORDER BY id ASC;>>>%s,1";
}
