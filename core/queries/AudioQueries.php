<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("core/queries/Queries.php");
includeOnce("core/models/Audio.php");
includeOnce("core/utils/StringUtils.php");
includeOnce("core/queries/UserPermissionQueries.php");
includeOnce("core/models/Speaker.php");

class AudioQueries extends Queries
{
    public static function queryActiveBySearchNameAndSeriesId($searchName, $speakerId, $seriesId)
    {
        $searchName = StringUtils::toSearchName($searchName);
        $seriesId = StringUtils::cleanAlphaNumber($seriesId);
        $speakerId = StringUtils::cleanAlphaNumber($speakerId);
        $sql = "SELECT l.* FROM Audio l JOIN AudioSeries se ON se.id = l.audio_series_id JOIN Speaker s ON s.id = l.speaker_id WHERE l.search_name = '$searchName' and l.speaker_id = $speakerId and l.audio_series_id = $seriesId AND l.status = 1 AND s.status = 1 AND se.status = 1 ORDER BY l.label_type DESC, l.id DESC LIMIT 1";
        $result = static::queryBySql($sql);
        if (count($result) > 0) {
            $result = $result[0];
        } else {
            $result = null;
        }
        return $result;
    }

    public static function queryActiveBySearchName($searchName, $speakerId)
    {
        $searchName = StringUtils::toSearchName($searchName);
        $speakerId = StringUtils::cleanAlphaNumber($speakerId);
        $sql = "SELECT * FROM Audio WHERE search_name = '$searchName' and speaker_id = $speakerId AND status = 1 AND is_private = 0 ORDER BY label_type DESC, id DESC LIMIT 1";
        $result = static::queryBySql($sql);
        if (count($result) > 0) {
            $result = $result[0];
        } else {
            $result = null;
        }
        return $result;
    }

    public static function queryActiveSoloBySpeakerId($speakerId, $offset = 0, $limit = 0, $categoryIds = array())
    {
        $speakerId = StringUtils::cleanNumber($speakerId);
        $offset = StringUtils::cleanNumber($offset);
        $limit = StringUtils::cleanNumber($limit);
        $categoryIds = StringUtils::cleanArrayNumber($categoryIds);
        $categoryIdsParam = "";
        if (count($categoryIds) > 0) {
            $categoryIdsCsv = implode(",", $categoryIds);
            $categoryIdsParam = " AND l.audio_category_id in ($categoryIdsCsv) ";
        }
        $sql = "SELECT l.* FROM Audio l JOIN Speaker s ON s.id = l.speaker_id WHERE l.speaker_id = $speakerId AND (l.audio_series_id = 0 OR l.audio_series_id IS NULL) AND l.status = 1 $categoryIdsParam AND l.status = 1 ORDER BY l.label_type DESC, l.id DESC";
        if ($limit > 0) {
            $limit = StringUtils::cleanNumber($limit);
            $sql .= " LIMIT $limit";
        }
        if ($offset > 0) {
            $sql .= " OFFSET $offset";
        }
        return static::queryBySql($sql);
    }
    public static function queryActiveSoloByCategoryId($categoryId, $offset = 0, $limit = 0)
    {
        $categoryId = StringUtils::cleanNumber($categoryId);
        $offset = StringUtils::cleanNumber($offset);
        $limit = StringUtils::cleanNumber($limit);
        $sql = "SELECT l.* FROM Audio l JOIN Speaker s ON s.id = l.speaker_id WHERE l.audio_category_id = $categoryId AND (l.audio_series_id = 0 OR l.audio_series_id IS NULL) AND l.status = 1 AND s.status = 1 ORDER BY l.label_type DESC, l.id DESC";
        if ($limit > 0) {
            $sql .= " LIMIT $limit";
        }
        if ($offset > 0) {
            $sql .= " OFFSET $offset";
        }
        return static::queryBySql($sql);
    }

    public static function queryActiveByAudioSeriesId($seriesId, $offset = 0, $limit = 0, $categoryIds = array())
    {
        $seriesId = StringUtils::cleanNumber($seriesId);
        $offset = StringUtils::cleanNumber($offset);
        $limit = StringUtils::cleanNumber($limit);
        $categoryIds = StringUtils::cleanArrayNumber($categoryIds);
        $categoryIdsParam = "";
        if (count($categoryIds) > 0) {
            $categoryIdsCsv = implode(",", $categoryIds);
            $categoryIdsParam = " AND l.audio_category_id in ($categoryIdsCsv) ";
        }
        $sql = "SELECT l.* FROM Audio l JOIN Speaker s ON s.id = l.speaker_id JOIN AudioSeries se ON se.id = l.audio_series_id WHERE l.audio_series_id = $seriesId AND l.status = 1 and s.status = 1 and se.status = 1 $categoryIdsParam AND l.is_private = 0 ORDER BY coalesce(l.sort_order_in_series, l.id) ASC";
        if ($limit > 0) {
            $sql .= " LIMIT $limit";
        }
        if ($offset > 0) {
            $sql .= " OFFSET $offset";
        }
        return static::queryBySql($sql);
    }

    public static function incrementViews($id)
    {
        $id = StringUtils::cleanNumber($id);
        $sql = "UPDATE Audio SET views = views + 1 WHERE id = $id";
        return static::executeSimple($sql);
    }
    public static function queryByIds($ids, $categoryIds = array())
    {
        $ids = StringUtils::cleanArrayNumber($ids);
        if (count($ids) == 0) {
            return array();
        }
        $categoryIds = StringUtils::cleanArrayNumber($categoryIds);
        $categoryIdsParam = "";
        if (count($categoryIds) > 0) {
            $categoryIdsCsv = implode(",", $categoryIds);
            $categoryIdsParam = " AND l.audio_category_id in ($categoryIdsCsv) ";
        }
        $csv = implode(",", $ids);
        $useStrictCsvOrder = true;
        $orderFieldParam = $useStrictCsvOrder ? "l.id, $csv" : "l.sort_order_in_series, l.id, $csv";
        $permissionAccessPrivateAudioSeries = UserPermissionType::AccessPrivateAudioSeries;
        $permissionAccessPrivateAudio = UserPermissionType::AccessPrivateAudio;
        $user = AccountUtils::isLoggedIn() ? AccountUtils::currentUser() : null;
        if ($user !== null && $user->hasPreference(UserPreference::ProtectionMode)) {
            $user = null;
        }
        if ($user !== null && $user->hasPermission(GeneralUserPermission::PrivateAudio)) {
            $sql = "SELECT * FROM Audio l WHERE id in ($csv) AND status = 1 $categoryIdsParam ORDER BY FIELD($orderFieldParam)";
        } else {
            if ($user !== null) {
                $sql = "SELECT l.* FROM Audio l LEFT JOIN UserPermission ap ON ((l.id = ap.source_id AND ap.type = $permissionAccessPrivateAudio) OR (l.audio_series_id != 0 AND l.audio_series_id is not null AND l.audio_series_id = ap.source_id AND ap.type = $permissionAccessPrivateAudioSeries)) AND ap.status = 1 AND ap.user_id = $user->id WHERE l.id in ($csv) AND l.status = 1 $categoryIdsParam AND (l.is_private = 0 OR ap.id IS NOT NULL) ORDER BY FIELD($orderFieldParam)";
            } else {
                $sql = "SELECT * FROM Audio l WHERE id in ($csv) AND l.status = 1 $categoryIdsParam AND l.is_private = 0 ORDER BY FIELD($orderFieldParam)";
            }
        }

        return static::queryBySql($sql, array());
    }

    public static function queryByIdsAndSpeaker($ids, $speakerId, $categoryIds = array())
    {
        $ids = StringUtils::cleanArrayNumber($ids);
        if (count($ids) == 0) {
            return array();
        }
        $categoryIds = StringUtils::cleanArrayNumber($categoryIds);
        $categoryIdsParam = "";
        if (count($categoryIds) > 0) {
            $categoryIdsCsv = implode(",", $categoryIds);
            $categoryIdsParam = " AND l.audio_category_id in ($categoryIdsCsv) ";
        }
        $speakerId = StringUtils::cleanNumber($speakerId);
        $csv = implode(",", $ids);
        $orderFieldParam = "l.id, $csv";
        $permissionAccessPrivateAudioSeries = UserPermissionType::AccessPrivateAudioSeries;
        $permissionAccessPrivateAudio = UserPermissionType::AccessPrivateAudio;
        $user = AccountUtils::isLoggedIn() ? AccountUtils::currentUser() : null;
        if ($user !== null && $user->hasPreference(UserPreference::ProtectionMode)) {
            $user = null;
        }
        if ($user !== null && $user->hasPermission(GeneralUserPermission::PrivateAudio)) {
            $sql = "SELECT * FROM Audio l WHERE l.id in ($csv) AND l.speaker_id = $speakerId AND l.status = 1 $categoryIdsParam ORDER BY FIELD(l.id, $csv)";
        } else {
            if ($user !== null) {
                $sql = "SELECT l.* FROM Audio l LEFT JOIN UserPermission ap ON ((l.id = ap.source_id AND ap.type = $permissionAccessPrivateAudio) OR (l.audio_series_id != 0 AND l.audio_series_id is not null AND l.audio_series_id = ap.source_id AND ap.type = $permissionAccessPrivateAudioSeries)) AND ap.status = 1 AND ap.user_id = $user->id WHERE l.id in ($csv) AND l.speaker_id = $speakerId AND l.status = 1 $categoryIdsParam AND (l.is_private = 0 OR ap.id IS NOT NULL) ORDER BY FIELD(l.id, $csv)";
            } else {
                $sql = "SELECT * FROM Audio l WHERE l.id in ($csv) AND l.speaker_id = $speakerId AND l.status = 1 $categoryIdsParam AND l.is_private = 0 ORDER BY FIELD(l.id, $csv)";
            }
        }

        return static::queryBySql($sql, array());
    }

    public static function queryByIdsAndCategory($ids, $categoryId)
    {
        $ids = StringUtils::cleanArrayNumber($ids);
        if (count($ids) == 0) {
            return array();
        }
        $categoryId = StringUtils::cleanNumber($categoryId);
        $csv = implode(",", $ids);
        $permissionAccessPrivateAudioSeries = UserPermissionType::AccessPrivateAudioSeries;
        $permissionAccessPrivateAudio = UserPermissionType::AccessPrivateAudio;
        $user = AccountUtils::isLoggedIn() ? AccountUtils::currentUser() : null;
        if ($user !== null && $user->hasPreference(UserPreference::ProtectionMode)) {
            $user = null;
        }
        if ($user !== null && $user->hasPermission(GeneralUserPermission::PrivateAudio)) {
            $sql = "SELECT * FROM Audio l WHERE l.id in ($csv) AND l.audio_category_id = $categoryId AND l.status = 1 ORDER BY FIELD(l.id, $csv)";
        } else {
            if ($user !== null) {
                $sql = "SELECT l.* FROM Audio l LEFT JOIN UserPermission ap ON ((l.id = ap.source_id AND ap.type = $permissionAccessPrivateAudio) OR (l.audio_series_id != 0 AND l.audio_series_id is not null AND l.audio_series_id = ap.source_id AND ap.type = $permissionAccessPrivateAudioSeries)) AND ap.status = 1 AND ap.user_id = $user->id WHERE l.id in ($csv) AND l.audio_category_id = $categoryId AND l.status = 1 AND (l.is_private = 0 OR ap.id IS NOT NULL) ORDER BY FIELD(l.id, $csv)";
            } else {
                $sql = "SELECT * FROM Audio l WHERE l.id in ($csv) AND l.audio_category_id = $categoryId AND l.status = 1 AND l.is_private = 0 ORDER BY FIELD(l.id, $csv)";
            }
        }

        return static::queryBySql($sql, array());
    }

    public static function queryByIdsSpeakerAndSeries($ids, $speakerId, $seriesId, $categoryIds = array())
    {
        $ids = StringUtils::cleanArrayNumber($ids);
        if (count($ids) == 0) {
            return array();
        }
        $categoryIds = StringUtils::cleanArrayNumber($categoryIds);
        $categoryIdsParam = "";
        if (count($categoryIds) > 0) {
            $categoryIdsCsv = implode(",", $categoryIds);
            $categoryIdsParam = " AND l.audio_category_id in ($categoryIdsCsv) ";
        }
        $speakerId = StringUtils::cleanNumber($speakerId);
        $seriesId = StringUtils::cleanNumber($seriesId);
        $csv = implode(",", $ids);
        $orderFieldParam = "l.id, $csv";
        $permissionAccessPrivateAudioSeries = UserPermissionType::AccessPrivateAudioSeries;
        $permissionAccessPrivateAudio = UserPermissionType::AccessPrivateAudio;
        $user = AccountUtils::isLoggedIn() ? AccountUtils::currentUser() : null;
        if ($user !== null && $user->hasPreference(UserPreference::ProtectionMode)) {
            $user = null;
        }
        if ($user !== null && $user->hasPermission(GeneralUserPermission::PrivateAudio)) {
            $sql = "SELECT * FROM Audio l WHERE id in ($csv) AND speaker_id = $speakerId AND audio_series_id = $seriesId AND status = 1 $categoryIdsParam ORDER BY FIELD(l.id, $csv)";
        } else {
            if ($user !== null) {
                $sql = "SELECT l.* FROM Audio l LEFT JOIN UserPermission ap ON ((l.id = ap.source_id AND ap.type = $permissionAccessPrivateAudio) OR (l.audio_series_id != 0 AND l.audio_series_id is not null AND l.audio_series_id = ap.source_id AND ap.type = $permissionAccessPrivateAudioSeries)) AND ap.status = 1 AND ap.user_id = $user->id WHERE l.id in ($csv) AND l.speaker_id = $speakerId AND l.audio_series_id = $seriesId AND l.status = 1 $categoryIdsParam AND (l.is_private = 0 OR ap.id IS NOT NULL) ORDER BY FIELD(l.id, $csv)";
            } else {
                $sql = "SELECT * FROM Audio l WHERE id in ($csv) AND speaker_id = $speakerId AND audio_series_id = $seriesId AND status = 1 $categoryIdsParam AND is_private = 0 ORDER BY FIELD(l.id, $csv)";
            }
        }

        return static::queryBySql($sql, array());
    }

    public static function queryActiveBySortOrderInSeries($sortOrder, $seriesId, $categoryIds = array())
    {
        $sortOrder = StringUtils::cleanNumber($sortOrder);
        $seriesId = StringUtils::cleanNumber($seriesId);
        $categoryIds = StringUtils::cleanArrayNumber($categoryIds);
        $categoryIdsParam = "";
        if (count($categoryIds) > 0) {
            $categoryIdsCsv = implode(",", $categoryIds);
            $categoryIdsParam = " AND l.audio_category_id in ($categoryIdsCsv) ";
        }
        $permissionAccessPrivateAudioSeries = UserPermissionType::AccessPrivateAudioSeries;
        $permissionAccessPrivateAudio = UserPermissionType::AccessPrivateAudio;
        $user = AccountUtils::isLoggedIn() ? AccountUtils::currentUser() : null;
        if ($user !== null && $user->hasPreference(UserPreference::ProtectionMode)) {
            $user = null;
        }
        if ($user !== null && $user->hasPermission(GeneralUserPermission::PrivateAudio)) {
            $sql = "SELECT * FROM Audio l WHERE sort_order_in_series = $sortOrder AND audio_series_id = $seriesId  AND  status = 1 $categoryIdsParam  ORDER BY id ASC;";
        } else {
            if ($user !== null) {
                $sql = "SELECT l.* FROM Audio l LEFT JOIN UserPermission ap ON ((l.id = ap.source_id AND ap.type = $permissionAccessPrivateAudio) OR (l.audio_series_id != 0 AND l.audio_series_id is not null AND l.audio_series_id = ap.source_id AND ap.type = $permissionAccessPrivateAudioSeries)) AND ap.status = 1 AND ap.user_id = $user->id WHERE l.sort_order_in_series = $sortOrder AND l.audio_series_id = $seriesId AND l.status = 1  $categoryIdsParam AND (l.is_private = 0 OR ap.id IS NOT NULL)  ORDER BY id ASC;";
            } else {
                $sql = "SELECT * FROM Audio l WHERE sort_order_in_series = $sortOrder AND audio_series_id = $seriesId  AND is_private = 0  AND  status = 1 $categoryIdsParam ORDER BY id ASC;";
            }
        }
        $result = static::queryBySql($sql, array());
        if (count($result) == 0) {
            return null;
        }
        return $result[0];
    }

    public static function queryFeatured($limit = 3, $categoryIds = array())
    {
        $limit = StringUtils::cleanNumber($limit);
        if ($limit <= 0 || $limit > 50) {
            $limit = 3;
        }
        $categoryIds = StringUtils::cleanArrayNumber($categoryIds);
        $categoryIdsParam = "";
        if (count($categoryIds) > 0) {
            $categoryIdsCsv = implode(",", $categoryIds);
            $categoryIdsParam = " AND l.audio_category_id in ($categoryIdsCsv) ";
        }
        $sql = "SELECT l.* FROM Audio l left join AudioSeries on l.audio_series_id = AudioSeries.id WHERE (l.label_type | 1) = l.label_type AND (AudioSeries.id is null or (AudioSeries.status = 1 AND AudioSeries.is_private = 0)) AND l.status = 1 $categoryIdsParam AND l.is_private = 0 ORDER BY l.label_mark_date DESC LIMIT $limit";
        return static::queryBySql($sql);
    }

    public static function queryFeaturedBySpeaker($speakerId, $limit = 3, $categoryIds = array())
    {
        $speakerId = StringUtils::cleanNumber($speakerId);
        $limit = StringUtils::cleanNumber($limit);
        if ($limit <= 0 || $limit > 50) {
            $limit = 3;
        }
        $categoryIds = StringUtils::cleanArrayNumber($categoryIds);
        $categoryIdsParam = "";
        if (count($categoryIds) > 0) {
            $categoryIdsCsv = implode(",", $categoryIds);
            $categoryIdsParam = " AND l.audio_category_id in ($categoryIdsCsv) ";
        }
        $sql = "SELECT l.* FROM Audio l left join AudioSeries on l.audio_series_id = AudioSeries.id WHERE (l.label_type | 1) = l.label_type AND l.speaker_id = $speakerId AND (AudioSeries.id is null or (AudioSeries.status = 1 AND AudioSeries.is_private = 0)) AND l.status = 1 $categoryIdsParam AND l.is_private = 0 ORDER BY l.label_mark_date DESC LIMIT $limit";
        return static::queryBySql($sql);
    }

    public static function queryFeaturedByCategory($categoryId, $limit = 3)
    {
        $categoryId = StringUtils::cleanNumber($categoryId);
        $limit = StringUtils::cleanNumber($limit);
        if ($limit <= 0 || $limit > 50) {
            $limit = 3;
        }
        $sql = "SELECT l.* FROM Audio l left join AudioSeries on l.audio_series_id = AudioSeries.id WHERE (l.label_type | 1) = l.label_type AND l.audio_category_id = $categoryId AND (AudioSeries.id is null or (AudioSeries.status = 1 AND AudioSeries.is_private = 0)) AND l.status = 1 AND l.is_private = 0 ORDER BY l.label_mark_date DESC LIMIT $limit";
        return static::queryBySql($sql);
    }

    public static function queryFeaturedBySeries($seriesId, $limit = 3, $categoryIds = array())
    {
      $seriesId = StringUtils::cleanNumber($seriesId);
      $limit = StringUtils::cleanNumber($limit);
      if ($limit <= 0 || $limit > 50) {
          $limit = 3;
      }
      $categoryIds = StringUtils::cleanArrayNumber($categoryIds);
      $categoryIdsParam = "";
      if (count($categoryIds) > 0) {
          $categoryIdsCsv = implode(",", $categoryIds);
          $categoryIdsParam = " AND l.audio_category_id in ($categoryIdsCsv) ";
      }
      $sql = "SELECT l.* FROM Audio l left join AudioSeries on l.audio_series_id = AudioSeries.id WHERE (l.label_type | 1) = l.label_type AND l.audio_series_id = $seriesId AND (AudioSeries.id is null or (AudioSeries.status = 1 AND AudioSeries.is_private = 0)) AND l.status = 1 $categoryIdsParam AND l.is_private = 0 ORDER BY l.label_mark_date DESC LIMIT $limit";
      return static::queryBySql($sql);
    }

    public static function queryFeaturedBySpeakerAndSeries($speakerId, $seriesId, $limit = 3, $categoryIds = array())
    {
      // legacy note: we do not need speaker ID as we have series ID specified
      return static::queryFeaturedBySeries($seriesId, $limit, $categoryIds);
    }

    public static function queryMostRecent($offset = 0, $limit = 3, $categoryIds = array())
    {
        $config = Config::getInstance();
        $limit = StringUtils::cleanNumber($limit);
        if ($limit <= 0 || $limit > 10) {
            $limit = 3;
        }
        $categoryIds = StringUtils::cleanArrayNumber($categoryIds);
        $categoryIdsParam = "";
        if (count($categoryIds) > 0) {
            $categoryIdsCsv = implode(",", $categoryIds);
            $categoryIdsParam = " AND l.audio_category_id in ($categoryIdsCsv) ";
        }
        $speakerNewDays = intval($config->SPEAKER_NEW_TAG_DAYS) + 1;
        $newDays = intval($config->LECTURE_NEW_TAG_DAYS);
        $sql = "SELECT l.* FROM Audio l left join Speaker s on s.id = l.speaker_id left join AudioSeries ls on l.audio_series_id = ls.id where (ls.id is null or (ls.status = 1 AND ls.is_private = 0)) AND l.status = 1 $categoryIdsParam and l.date_added > (now() - INTERVAL $newDays DAY) and not s.date_added > (now() - INTERVAL $speakerNewDays DAY) order by l.id desc LIMIT $limit";
        if ($offset > 0) {
          $sql .= " OFFSET $offset";
        }
        return static::queryBySql($sql);
    }

    public static function queryActiveById($id)
    {
        $id = StringUtils::cleanNumber($id);
        $permissionAccessPrivateAudioSeries = UserPermissionType::AccessPrivateAudioSeries;
        $permissionAccessPrivateAudio = UserPermissionType::AccessPrivateAudio;
        $user = AccountUtils::isLoggedIn() ? AccountUtils::currentUser() : null;
        if ($user !== null && $user->hasPreference(UserPreference::ProtectionMode)) {
            $user = null;
        }
        if ($user !== null && $user->hasPermission(GeneralUserPermission::PrivateAudio)) {
            $sql = "SELECT * FROM Audio WHERE id = $id AND status = 1";
        } else {
            if ($user !== null) {
                $sql = "SELECT l.* FROM Audio l LEFT JOIN UserPermission ap ON ((l.id = ap.source_id AND ap.type = $permissionAccessPrivateAudio) OR (l.audio_series_id != 0 AND l.audio_series_id is not null AND l.audio_series_id = ap.source_id AND ap.type = $permissionAccessPrivateAudioSeries)) AND ap.status = 1 AND ap.user_id = $user->id WHERE l.id = $id AND l.status = 1 AND (l.is_private = 0 OR ap.id IS NOT NULL)";
            } else {
                $sql = "SELECT * FROM Audio WHERE id = $id AND status = 1 AND is_private = 0";
            }
        }
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
        $permissionAccessPrivateAudioSeries = UserPermissionType::AccessPrivateAudioSeries;
        $permissionAccessPrivateAudio = UserPermissionType::AccessPrivateAudio;
        $user = AccountUtils::isLoggedIn() ? AccountUtils::currentUser() : null;
        if ($user !== null && $user->hasPreference(UserPreference::ProtectionMode)) {
            $user = null;
        }
        if ($ignorePrivacy || ($user !== null && $user->hasPermission(GeneralUserPermission::PrivateAudio))) {
            $sql = "SELECT * FROM Audio WHERE id = $id";
        } else {
            if ($user !== null) {
                $sql = "SELECT l.* FROM Audio l LEFT JOIN UserPermission ap ON ((l.id = ap.source_id AND ap.type = $permissionAccessPrivateAudio) OR (l.audio_series_id != 0 AND l.audio_series_id is not null AND l.audio_series_id = ap.source_id AND ap.type = $permissionAccessPrivateAudioSeries)) AND ap.status = 1 AND ap.user_id = $user->id WHERE l.id = $id AND (l.is_private = 0 OR ap.id IS NOT NULL)";
            } else {
                $sql = "SELECT * FROM Audio WHERE id = $id AND is_private = 0";
            }
        }
        $result = static::queryBySql($sql);
        if (count($result) == 0) {
            return null;
        }
        return $result[0];
    }

    public static function queryLastSortOrder($seriesId)
    {
        $seriesId = StringUtils::cleanNumber($seriesId);
        $queryResult = static::querySimpleSql("SELECT sort_order_in_series as val FROM Audio WHERE audio_series_id = $seriesId order by sort_order_in_series DESC limit 1");
        return intval($queryResult[0]["val"]);
    }

    public static function queryWithInvalidThumbnails($limit = 10)
    {
        $config = Config::getInstance();
        $sql = "SELECT * FROM Audio WHERE (thumb_url is null OR (thumb_url not like '$config->STATIC_IMAGES_BASE%' AND thumb_url not like '%-thumb.jpg') OR (image_ref not like '$config->STATIC_IMAGES_BASE%' AND image_ref not like '%-s.jpg')) AND ((thumb_url is not null AND thumb_url != '') OR (image_ref is not null AND image_ref != '')) order by image_ref desc, thumb_url desc LIMIT $limit";
        return static::queryBySql($sql);
    }

    public static function queryWithConvertableAudios($limit = 10, $type = array(1, 2) /* youtube and mp3 */)
    {
        $config = Config::getInstance();
        $types = StringUtils::cleanArrayNumber($type);
        $typesParam = " ";
        if (!empty($types)) {
            $typesCsv = implode(",", $types);
            $typesParam = " AND type in ($typesCsv) ";
        }
        $sql = "SELECT * FROM Audio WHERE (convert_to_audio = 1 $typesParam) AND status = 1 order by type DESC, last_updated ASC LIMIT $limit";
        return static::queryBySql($sql);
    }

    public static function querySoloActiveForRSS()
    {
        return static::queryBySql("SELECT l.* FROM Audio l JOIN Speaker s ON s.id = l.speaker_id WHERE l.is_private = 0 AND l.status = 1 AND l.audio_series_id = 0 AND s.status = 1 ORDER BY l.id DESC LIMIT 20;", array());
    }

    public static function queryActiveSoloBySpeakerIdForRSS($speakerId)
    {
        $speakerId = StringUtils::cleanNumber($speakerId);
        return static::queryBySql("SELECT l.* FROM Audio l JOIN Speaker s ON s.id = l.speaker_id WHERE l.speaker_id = ? AND l.is_private = 0 AND l.status = 1 AND l.audio_series_id = 0 AND s.status = 1 ORDER BY l.id DESC LIMIT 20;", array($speakerId));
    }

    public static function queryActiveSoloByCategoryIdForRSS($categoryId)
    {
        $categoryId = StringUtils::cleanNumber($categoryId);
        return static::queryBySql("SELECT l.* FROM Audio l JOIN Speaker s ON s.id = l.speaker_id WHERE l.audio_category_id = ? AND l.is_private = 0 AND l.status = 1 AND l.audio_series_id = 0 AND s.status = 1 ORDER BY l.id DESC LIMIT 20;", array($categoryId));
    }

    public static function queryActiveSoloBySpeakerAndCategoryIdForRSS($speakerId, $categoryId)
    {
        $speakerId = StringUtils::cleanNumber($speakerId);
        $categoryId = StringUtils::cleanNumber($categoryId);
        return static::queryBySql("SELECT l.* FROM Audio l JOIN Speaker s ON s.id = l.speaker_id WHERE l.speaker_id = ? and l.audio_category_id = ? AND l.is_private = 0 AND l.status = 1 AND l.audio_series_id = 0 AND s.status = 1 ORDER BY l.id DESC LIMIT 20;", array($speakerId, $categoryId));
    }

    public static function queryActiveSoloBySeriesIdForRSS($seriesId)
    {
        $seriesId = StringUtils::cleanNumber($seriesId);
        return static::queryBySql("SELECT l.* FROM Audio l JOIN Speaker s ON s.id = l.speaker_id WHERE l.is_private = 0 AND l.status = 1 AND l.audio_series_id = ? AND s.status = 1 ORDER BY l.id DESC LIMIT 20;", array($seriesId));
    }


    public static function getNewAudioCount()
    {
        $config = Config::getInstance();
        $speakerIdClause = "";
        if (isset($_REQUEST["speakerId"])) {
            $id = Speaker::getActualId(urldecode($_REQUEST["speakerId"]));
            if ($id !== -1) {
                $speakerIdClause = "speaker_id = $id AND";
            }
        }
        $newSoloSql = "SELECT count(*) as value FROM Audio WHERE $speakerIdClause audio_series_id = 0 AND status = ? AND is_private = 0 AND date_added > (now() - INTERVAL ? DAY)";
        $newAudiosInSeriesSql = "SELECT count(*) as value FROM Audio WHERE $speakerIdClause (SELECT date_added FROM AudioSeries WHERE id = audio_series_id AND DATEDIFF(CURRENT_DATE, date_added) > ?) AND status = ? AND is_private = 0 AND date_added > (now() - INTERVAL ? DAY)";

        $newSolo = static::querySimpleSql($newSoloSql, array(1, $config->LECTURE_NEW_TAG_DAYS));
        $newAudiosInSeries = static::querySimpleSql($newAudiosInSeriesSql, array($config->LECTURE_NEW_TAG_DAYS, 1, $config->LECTURE_NEW_TAG_DAYS));

        return array("nso" => (int)$newSolo[0]["value"], "nls" => (int)$newAudiosInSeries[0]["value"]);
    }

    public static function updateSearchNames()
    {
        static::_updateSearchNames("name", "searchName");
    }


    public static function queryLocalWithInvalidFields($limit = 10)
    {
        $config = Config::getInstance();
        $vagueStaticBase = str_replace("http://rc", "//rc", $config->STATIC_RESOURCES_BASE);
        $vagueStaticBase = str_replace("https://rc", "//rc", $vagueStaticBase);
        $sql = "SELECT * FROM Audio WHERE (length IS NULL OR length = 0 OR file_size IS NULL OR file_size = 0) AND (url like '$config->STATIC_RESOURCES_BASE%' OR url like '$vagueStaticBase%') ORDER BY last_updated DESC LIMIT $limit";
        return static::queryBySql($sql);
    }


    public static function queryNonLocalWithInvalidFields($limit = 10)
    {
        $config = Config::getInstance();
        $vagueStaticBase = str_replace("http://rc", "//rc", $config->STATIC_RESOURCES_BASE);
        $vagueStaticBase = str_replace("https://rc", "//rc", $vagueStaticBase);

        $sql = "SELECT * FROM Audio WHERE (length IS NULL OR file_size IS NULL) AND (url not like '$config->STATIC_RESOURCES_BASE%' AND url not like '$vagueStaticBase%') ORDER BY last_updated DESC LIMIT $limit";
        return static::queryBySql($sql);
    }
}
