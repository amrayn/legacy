<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("core/queries/Queries.php");
includeOnce("core/models/Speaker.php");
includeOnce("core/utils/StringUtils.php");

class SpeakerQueries extends Queries
{

    public static function queryAllActiveAndCounts()
    {
        $queryResultSql = "SELECT Speaker.*, (SELECT count(*) FROM Audio WHERE speaker_id = Speaker.id AND audio_series_id = 0 AND status = 1 AND is_private = 0) as total_lectures, (SELECT count(*) FROM AudioSeries WHERE speaker_id = Speaker.id AND status = 1 AND is_private = 0) as total_lecture_series, (SELECT count(*) FROM Audio WHERE speaker_id = Speaker.id AND audio_series_id = 0 AND status = 1 AND is_private = 0 AND date_added > (now() - INTERVAL (SELECT `value` FROM ConfigData WHERE `key` = 'LECTURE_NEW_TAG_DAYS') DAY)) as new_solo_lectures_count, (SELECT count(*) FROM Audio WHERE speaker_id = Speaker.id AND (SELECT date_added FROM AudioSeries WHERE id = audio_series_id AND DATEDIFF(CURRENT_DATE, date_added) > (SELECT `value` FROM ConfigData WHERE `key` = 'LECTURE_NEW_TAG_DAYS')) AND status = 1 AND is_private = 0 AND DATEDIFF(CURRENT_DATE, date_added) < (SELECT `value` FROM ConfigData WHERE `key` = 'LECTURE_NEW_TAG_DAYS')) as new_lectures_in_series_count, (SELECT count(*) FROM AudioSeries WHERE speaker_id = Speaker.id AND status = 1 AND is_private = 0 AND DATEDIFF(CURRENT_DATE, date_added) <= (SELECT `value` FROM ConfigData WHERE `key` = 'LECTURE_NEW_TAG_DAYS')) as new_lecture_series_count FROM Speaker WHERE status = 1 ORDER BY sort_order, date_added";

        $nonObjFields = array(
            "total_lectures",
            "total_lecture_series",
            "new_solo_lectures_count",
            "new_lectures_in_series_count",
            "new_lecture_series_count"
        );
        return static::queryNonStandardSql($queryResultSql, $nonObjFields, "speaker");
    }

    public static function queryActiveBySearchName($name)
    {
        $name = StringUtils::toSearchName($name);
        $sql = "SELECT * FROM Speaker WHERE (search_name = '$name' OR legacy_search_name = '$name') AND status = 1";
        $result = static::queryBySql($sql);
        if (count($result) == 0) {
            return null;
        }
        return $result[0];
    }

    public static function queryMostRecent($offset = 0, $limit = 3)
    {
        $config = Config::getInstance();
        $limit = StringUtils::cleanNumber($limit);
        if ($limit <= 0 || $limit > 10) {
            $limit = 3;
        }
        $newDays = $config->SPEAKER_NEW_TAG_DAYS;
        $sql = "SELECT s.*, (SELECT count(*) FROM Audio WHERE speaker_id = s.id AND audio_series_id = 0 AND status = 1) as total_lectures, (SELECT count(*) FROM AudioSeries WHERE speaker_id = s.id AND status = 1) as total_lecture_series";
        $sql .= " FROM Speaker s where s.status = 1 and s.date_added > (now() - INTERVAL $newDays DAY) order by s.id desc LIMIT $limit";
        if ($offset > 0) {
          $sql .= " OFFSET $offset";
        }
        $nonObjFields = array(
            "total_lectures",
            "total_lecture_series",
        );
        return static::queryNonStandardSql($sql, $nonObjFields, "speaker");
    }

    public static function queryBySearchName($name)
    {
        $name = StringUtils::toSearchName($name);
        $sql = "SELECT * FROM Speaker WHERE (search_name = '$name' OR legacy_search_name = '$name')";
        $result = static::queryBySql($sql);
        if (count($result) == 0) {
            return null;
        }
        return $result[0];
    }

    public static function updateSearchNames()
    {
        static::_updateSearchNames("name", "searchName");
    }

    public static function queryWithInvalidThumbnails($limit = 10)
    {
        $config = Config::getInstance();
        $sql = "SELECT * FROM Speaker WHERE (thumb_url is null OR (thumb_url not like '$config->STATIC_IMAGES_BASE%' AND thumb_url not like '%-thumb.jpg') OR (image_ref not like '$config->STATIC_IMAGES_BASE%' AND image_ref not like '%-s.jpg')) AND ((thumb_url is not null AND thumb_url != '') OR (image_ref is not null AND image_ref != '')) order by image_ref desc, thumb_url desc LIMIT $limit";
        return static::queryBySql($sql);
    }
}
