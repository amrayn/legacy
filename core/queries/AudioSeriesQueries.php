<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("core/queries/Queries.php");
includeOnce("core/queries/UserPermissionQueries.php");
includeOnce("core/models/AudioSeries.php");
includeOnce("core/models/Speaker.php");

class AudioSeriesQueries extends Queries
{
    private static function buildItemSql($extraClauses = array(), $categoryIds = array())
    {
        $categoryIds = StringUtils::cleanArrayNumber($categoryIds);
        $categoryIdsParam = "";
        if (count($categoryIds) > 0) {
            $categoryIdsCsv = implode(",", $categoryIds);
            $categoryIdsParam = " AND se.audio_category_id in ($categoryIdsCsv) ";
        }
        $sql = "SELECT se.*, (SELECT count(*) FROM Audio WHERE audio_series_id = se.id AND status = 1) as total_lectures, (SELECT count(*) FROM Audio WHERE audio_series_id = se.id AND status = 1 AND type in (1,6)) as total_audios, (SELECT count(*) FROM Audio WHERE audio_series_id = se.id AND status = 1 AND type in (2,3,4,5,7)) as total_videos, (SELECT sum(length) FROM Audio WHERE audio_series_id = se.id AND status = 1) as total_length, (SELECT count(*) FROM Audio WHERE audio_series_id = se.id AND status = 1 AND date_added > (now() - INTERVAL (SELECT `value` FROM ConfigData WHERE `key` = 'LECTURE_NEW_TAG_DAYS') DAY)) as new_lectures_count, (SELECT count(*) FROM Audio l LEFT JOIN AudioNote ln ON l.id = ln.audio_id WHERE l.audio_series_id = se.id AND ln.status = 1 AND l.status=1 AND ln.is_default = true) as total_with_lecture_notes FROM AudioSeries se JOIN Speaker s ON s.id = se.speaker_id WHERE " .  implode(" AND ", $extraClauses) . (count($extraClauses) > 0 ? " AND " : "") . " se.status = 1 AND s.status = 1 $categoryIdsParam ORDER BY se.date_added DESC";
        return $sql;
    }

    private static function queryItem($sql)
    {
        $nonObjFields = array(
            "total_lectures",
            "total_audios",
            "total_videos",
            "new_lectures_count",
            "total_length",
            "total_with_lecture_notes"
        );
        return static::queryNonStandardSql($sql, $nonObjFields, "series");
    }

    public static function queryActiveBySpeakerId($speakerId, $offset = 0, $limit = 0, $categoryIds = array())
    {
        $speakerId = StringUtils::cleanNumber($speakerId);
        $offset = StringUtils::cleanNumber($offset);
        $limit = StringUtils::cleanNumber($limit);
        $sql = static::buildItemSql(array("se.speaker_id = $speakerId"), $categoryIds);
        if ($limit > 0) {
            $sql .= " LIMIT $limit";
        }
        if ($offset > 0) {
            $sql .= " OFFSET $offset";
        }
        return static::queryItem($sql);
    }

    public static function queryActiveBySpeakerIdForSitemap($speakerId, $offset = 0, $limit = 0)
    {
        $speakerId = StringUtils::cleanNumber($speakerId);
        $offset = StringUtils::cleanNumber($offset);
        $limit = StringUtils::cleanNumber($limit);
        $sql = "SELECT ls.* FROM AudioSeries ls JOIN Speaker s on s.id = ls.speaker_id WHERE ls.sitemap_written = 0 AND ls.speaker_id = $speakerId AND ls.status = 1 AND s.status = 1 order by ls.id";
        if ($limit > 0) {
            $sql .= " LIMIT $limit";
        }
        if ($offset > 0) {
            $sql .= " OFFSET $offset";
        }
        return static::queryBySql($sql);
    }

    public static function queryActiveBySpeakerIdForSitemapWhereWritten($speakerId, $offset = 0, $limit = 0)
    {
        $speakerId = StringUtils::cleanNumber($speakerId);
        $offset = StringUtils::cleanNumber($offset);
        $limit = StringUtils::cleanNumber($limit);
        $sql = "SELECT ls.* FROM AudioSeries ls JOIN Speaker s on s.id = ls.speaker_id WHERE sitemap_written = 1 AND speaker_id = $speakerId AND ls.status = 1 AND s.status = 1 order by ls.id";
        if ($limit > 0) {
            $sql .= " LIMIT $limit";
        }
        if ($offset > 0) {
            $sql .= " OFFSET $offset";
        }
        return static::queryBySql($sql);
    }

    public static function queryActiveByCategoryId($categoryId, $offset = 0, $limit = 0)
    {
        $categoryId = StringUtils::cleanNumber($categoryId);
        $offset = StringUtils::cleanNumber($offset);
        $limit = StringUtils::cleanNumber($limit);
        $sql = static::buildItemSql(array(), array($categoryId));
        if ($limit > 0) {
            $sql .= " LIMIT $limit";
        }
        if ($offset > 0) {
            $sql .= " OFFSET $offset";
        }
        return static::queryItem($sql);
    }

    public static function queryByIdsAndSpeaker($ids, $speakerId, $categoryIds = array())
    {
        $ids = StringUtils::cleanArrayNumber($ids);
        $speakerId = StringUtils::cleanNumber($speakerId);
        $csv = implode(",", $ids);
        $categoryIds = StringUtils::cleanArrayNumber($categoryIds);
        $categoryIdsParam = "";
        if (count($categoryIds) > 0) {
            $categoryIdsCsv = implode(",", $categoryIds);
            $categoryIdsParam = " AND ls.audio_category_id in ($categoryIdsCsv) ";
        }
        $sql = "SELECT ls.* FROM AudioSeries ls JOIN Speaker s on s.id = ls.speaker_id WHERE ls.id in ($csv) AND ls.speaker_id = $speakerId AND ls.status = 1 AND s.status = 1 $categoryIdsParam ORDER BY FIELD(ls.id, $csv)";

        return static::queryBySql($sql, array());
    }

    public static function queryByIdsAndCategory($ids, $categoryId)
    {
        $ids = StringUtils::cleanArrayNumber($ids);
        $categoryId = StringUtils::cleanNumber($categoryId);
        $csv = implode(",", $ids);

        $sql = "SELECT ls.* FROM AudioSeries ls JOIN Speaker s on s.id = ls.speaker_id WHERE ls.id in ($csv) AND audio_category_id = $categoryId AND ls.status = 1 AND s.status = 1 ORDER BY FIELD(ls.id, $csv)";

        return static::queryBySql($sql, array());
    }

    public static function queryByIds($ids, $categoryIds = array())
    {
        $ids = StringUtils::cleanArrayNumber($ids);
        $csv = implode(",", $ids);
        if (empty($ids)) {
            return array();
        }
        $sql = static::buildItemSql(array("se.id in ($csv)"), $categoryIds);

        return static::queryItem($sql);
    }

    public static function queryActiveSeriesItemById($id, $categoryIds = array())
    {
        $id = StringUtils::cleanNumber($id);
        $sql = static::buildItemSql(array("se.id = $id"), $categoryIds);
        $resultItems = static::queryItem($sql);
        if (count($resultItems) > 0) {
            return $resultItems[0];
        }
        return null;
    }

    public static function queryActiveSeriesItemBySearchName($searchName, $speakerId, $categoryIds = array())
    {
        $searchName = StringUtils::toSearchName($searchName);
        $speakerId = StringUtils::cleanNumber($speakerId);
        $sql = static::buildItemSql(array("(se.search_name = '$searchName' OR se.legacy_search_name = '$searchName')", "se.speaker_id = $speakerId"), $categoryIds);
        $resultItems = static::queryItem($sql);
        if (count($resultItems) > 0) {
            return $resultItems[0];
        }
        return null;
    }

    public static function queryActiveBySearchName($name, $speakerId)
    {
        // DO NOT USE THIS EXCEPT WHERE IT'S ALREADY USED - because we need is_private
        $name = StringUtils::toSearchName($name);
        $speakerId = StringUtils::cleanNumber($speakerId);
        $sql = "SELECT ls.* FROM AudioSeries ls JOIN Speaker s on s.id = ls.speaker_id WHERE (ls.search_name = '$name' OR ls.legacy_search_name = '$name') AND ls.speaker_id = $speakerId AND ls.status = 1 AND s.status = 1";
        $result = static::queryBySql($sql);
        if (count($result) == 0) {
            return null;
        }
        return $result[0];
    }

    public static function queryBySearchName($name, $speakerId)
    {
        // DO NOT USE THIS EXCEPT WHERE IT'S ALREADY USED - because we need is_private
        $name = StringUtils::toSearchName($name);
        $speakerId = StringUtils::cleanNumber($speakerId);
        $sql = "SELECT ls.* FROM AudioSeries ls JOIN Speaker s on s.id = ls.speaker_id WHERE (ls.search_name = '$name' OR ls.legacy_search_name = '$name') AND ls.speaker_id = $speakerId AND s.status = 1";
        $result = static::queryBySql($sql);
        if (count($result) == 0) {
            return null;
        }
        return $result[0];
    }

    public static function queryActiveCount($speakerId, $categoryIds = array())
    {
        $speakerId = StringUtils::cleanNumber($speakerId);
        $categoryIds = StringUtils::cleanArrayNumber($categoryIds);
        $categoryIdsParam = "";
        if (count($categoryIds) > 0) {
            $categoryIdsCsv = implode(",", $categoryIds);
            $categoryIdsParam = " AND audio_category_id in ($categoryIdsCsv) ";
        }
        $queryResult = static::querySimpleSql("SELECT count(*) as total FROM AudioSeries ls JOIN Speaker s on s.id = ls.speaker_id WHERE speaker_id = $speakerId AND ls.status = 1 and s.status = 1 $categoryIdsParam");
        return intval($queryResult[0]["total"]);
    }

    public static function queryActiveCountByCategory($categoryId)
    {
        $categoryId = StringUtils::cleanNumber($categoryId);

        $queryResult = static::querySimpleSql("SELECT count(*) as total FROM AudioSeries ls JOIN Speaker s on s.id = ls.speaker_id WHERE audio_category_id = $categoryId AND ls.status = 1 AND s.status = 1");
        return intval($queryResult[0]["total"]);
    }

    public static function updateSearchNames()
    {
        static::_updateSearchNames("name", "searchName");
    }

    public static function queryActiveById($id)
    {
        $id = StringUtils::cleanNumber($id);
        $sql = "SELECT ls.* FROM AudioSeries ls JOIN Speaker s on s.id = ls.speaker_id WHERE ls.id = $id AND ls.status = 1 AND s.status = 1";
        $result = static::queryBySql($sql);
        if (count($result) == 0) {
            return null;
        }
        return $result[0];
    }

    /** Override because we shouldnt give access for users that cannot access this audio */
    public static function queryById($id, $ignorePrivacy = false)
    {
        $id = StringUtils::cleanNumber($id);
        $sql = "SELECT ls.* FROM AudioSeries ls JOIN Speaker s on s.id = ls.speaker_id WHERE ls.id = $id AND s.status = 1";
        $result = static::queryBySql($sql);
        if (count($result) == 0) {
            return null;
        }
        return $result[0];
    }

    public static function queryWithInvalidThumbnails($limit = 10)
    {
        $config = Config::getInstance();
        $sql = "SELECT ls.* FROM AudioSeries ls JOIN Speaker s on s.id = ls.speaker_id WHERE (ls.thumb_url is null OR (ls.thumb_url not like '$config->STATIC_IMAGES_BASE%' AND ls.thumb_url not like '%-thumb.jpg') OR (ls.image_ref not like '$config->STATIC_IMAGES_BASE%' AND ls.image_ref not like '%-s.jpg')) AND ((ls.thumb_url is not null AND ls.thumb_url != '') OR (ls.image_ref is not null AND ls.image_ref != '')) AND s.status = 1 order by ls.image_ref desc, ls.thumb_url desc LIMIT $limit";
        return static::queryBySql($sql);
    }

    public static function queryActiveBySpeakerIdForRSS($speakerId)
    {
        $speakerId = StringUtils::cleanNumber($speakerId);
        return static::queryBySql("SELECT ls.* FROM AudioSeries ls JOIN Speaker s ON s.id = ls.speaker_id  WHERE ls.is_private = 0 AND ls.status = 1 AND s.status = 1 AND ls.speaker_id = ? ORDER BY ls.id DESC LIMIT 20;", array($speakerId));
    }
    public static function queryActiveByCategoryIdForRSS($categoryId)
    {
        $categoryId = StringUtils::cleanNumber($categoryId);
        return static::queryBySql("SELECT ls.* FROM AudioSeries ls JOIN Speaker s ON s.id = ls.speaker_id  WHERE ls.is_private = 0 AND ls.status = 1 AND s.status = 1 AND ls.audio_category_id = ? ORDER BY ls.id DESC LIMIT 20;", array($categoryId));
    }

    public static function queryActiveBySpeakerAndCategoryIdForRSS($speakerId, $categoryId)
    {
        $speakerId = StringUtils::cleanNumber($speakerId);
        $categoryId = StringUtils::cleanNumber($categoryId);
        return static::queryBySql("SELECT ls.* FROM AudioSeries ls JOIN Speaker s ON s.id = ls.speaker_id  WHERE ls.is_private = 0 AND ls.status = 1 AND s.status = 1 AND ls.speaker_id = ? AND ls.audio_category_id = ? ORDER BY ls.id DESC LIMIT 20;", array($speakerId, $categoryId));
    }

    public static function queryActiveForRSS()
    {
        return static::queryBySql("SELECT ls.* FROM AudioSeries ls JOIN Speaker s ON s.id = ls.speaker_id  WHERE ls.is_private = 0 AND ls.status = 1 AND s.status = 1 ORDER BY ls.id DESC LIMIT 20;", array());
    }


    public static function queryNewAudioSeriesCount()
    {
        $config = Config::getInstance();
        $speakerIdClause = "";
        if (isset($_REQUEST["speakerId"])) {
            $id = Speaker::getActualId(urldecode($_REQUEST["speakerId"]));
            if ($id !== -1) {
                $speakerIdClause = "ls.speaker_id = $id AND";
            }
        }
        $newSeriesSql = "SELECT count(*) as value FROM AudioSeries ls JOIN Speaker s ON s.id = ls.speaker_id WHERE $speakerIdClause ls.status = ? AND s.status = 1 AND date_added > (now() - INTERVAL ? DAY)";

        $newSeries = static::querySimpleSql($newSeriesSql, array(1, $config->LECTURE_NEW_TAG_DAYS));

        return array("nse" => (int)$newSeries[0]["value"]);
    }

    public static function queryMostRecent($offset = 0, $limit = 3)
    {
        $config = Config::getInstance();
        $limit = StringUtils::cleanNumber($limit);
        if ($limit <= 0 || $limit > 10) {
            $limit = 3;
        }
        $newDays = $config->LECTURE_NEW_TAG_DAYS;

        $sql = static::buildItemSql(array("se.date_added > (now() - INTERVAL $newDays DAY)"));
        if ($limit > 0) {
            $sql .= " LIMIT $limit";
        }
        if ($offset > 0) {
            $sql .= " OFFSET $offset";
        }
        return static::queryItem($sql, $nonObjFields, "series");
    }
}
