<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("core/queries/Queries.php");
includeOnce("core/models/BookVolume.php");

class BookVolumeQueries extends Queries
{
    public static function queryActiveByBookId($bookId)
    {
        return static::queryBySql(static::selectAll() . " WHERE book_id = ? AND status=? order by sort_order;", array($bookId, 1));
    }
    public static function queryByIds($ids, $categoryIds = array())
    {
        if (count($ids) == 0) {
            return array();
        }
        $ids = StringUtils::cleanArrayNumber($ids);
        $categoryIds = StringUtils::cleanArrayNumber($categoryIds);
        $categoryIdsParam = "";
        if (count($categoryIds) > 0) {
            $categoryIdsCsv = implode(",", $categoryIds);
            $categoryIdsParam = " AND b.book_category_id in ($categoryIdsCsv) ";
        }
        $csv = implode(",", $ids);
        return static::queryBySql("SELECT bv.* FROM BookVolume bv JOIN Book b ON b.id = bv.book_id WHERE bv.id in ($csv) $categoryIdsParam AND b.status = 1 AND bv.status = 1 ORDER BY FIELD(bv.id, $csv)", array());
    }

    public static function queryActiveBySearchName($searchName, $bookId)
    {
        $searchName = StringUtils::toSearchName($searchName);
        $permissionAccessPrivateBook = UserPermissionType::AccessPrivateBook;
        $user = AccountUtils::isLoggedIn() ? AccountUtils::currentUser() : null;
        if ($user !== null && $user->hasPreference(UserPreference::ProtectionMode)) {
            $user = null;
        }
        if ($user !== null && $user->hasPermission(GeneralUserPermission::PrivateBook)) {
            $sql = "SELECT bv.* FROM BookVolume bv LEFT JOIN Book b on b.id = bv.book_id WHERE bv.search_name = ? AND bv.status = 1 AND b.status = 1 AND book_id = $bookId LIMIT 1;";
        } else {
            if ($user !== null) {
                $sql = "SELECT bv.* FROM BookVolume bv LEFT JOIN Book b on b.id = bv.book_id LEFT JOIN UserPermission up ON (b.id = up.source_id AND up.type = $permissionAccessPrivateBook AND up.status = 1 AND up.user_id = $user->id) WHERE bv.search_name = ? AND bv.status = 1 AND b.status = 1 AND (b.is_private = 0 OR up.id IS NOT NULL) AND book_id = $bookId LIMIT 1 ";
            } else {
                $sql = "SELECT bv.* FROM BookVolume bv LEFT JOIN Book b on b.id = bv.book_id WHERE bv.search_name = ? AND bv.status = 1 AND b.status = 1 AND b.is_private = 0 AND book_id = $bookId LIMIT 1 ";
            }
        }
        $result = static::queryBySql($sql, array($searchName));
        if (count($result) > 0) {
            return $result[0];
        }
        return null;
    }

    public static function updateSearchNames()
    {
        static::_updateSearchNames("name", "searchName");
    }


    public static function queryLastSortOrder($bookId)
    {
        $bookId = StringUtils::cleanNumber($bookId);
        $queryResult = static::querySimpleSql("SELECT sort_order as val FROM BookVolume WHERE book_id = $bookId order by sort_order desc limit 1");
        return intval($queryResult[0]["val"]);
    }

    public static function queryWithInvalidThumbnails($limit = 10)
    {
        $config = Config::getInstance();
        $sql = "SELECT * FROM BookVolume WHERE (thumb_url is null OR (thumb_url not like '$config->STATIC_IMAGES_BASE%' AND thumb_url not like '%-thumb.jpg') OR (image_ref not like '$config->STATIC_IMAGES_BASE%' AND image_ref not like '%-s.jpg')) AND ((thumb_url is not null AND thumb_url != '') OR (image_ref is not null AND image_ref != '')) order by image_ref desc, thumb_url desc LIMIT $limit";
        return static::queryBySql($sql);
    }

    public static function queryLocalWithInvalidFields($limit = 10)
    {
        $config = Config::getInstance();
        $vagueStaticBase = str_replace("http://rc", "//rc", $config->STATIC_RESOURCES_BASE);
        $vagueStaticBase = str_replace("https://rc", "//rc", $vagueStaticBase);

        $sql = "SELECT * FROM BookVolume WHERE (pages IS NULL OR pages = 0 OR file_size IS NULL OR file_size = 0) AND (url like '$config->STATIC_RESOURCES_BASE%' OR url like '$vagueStaticBase%') ORDER BY last_updated DESC LIMIT $limit";
        return static::queryBySql($sql);
    }

    public static function queryNonLocalWithInvalidFields($limit = 10)
    {
        $config = Config::getInstance();
        $vagueStaticBase = str_replace("http://rc", "//rc", $config->STATIC_RESOURCES_BASE);
        $vagueStaticBase = str_replace("https://rc", "//rc", $vagueStaticBase);

        $sql = "SELECT * FROM BookVolume WHERE (pages IS NULL OR file_size IS NULL) AND (url not like '$config->STATIC_RESOURCES_BASE%' AND url not like '$vagueStaticBase%') ORDER BY last_updated DESC LIMIT $limit";
        return static::queryBySql($sql);
    }
}
