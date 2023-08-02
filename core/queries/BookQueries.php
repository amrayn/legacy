<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("core/queries/Queries.php");
includeOnce("core/models/Book.php");
includeOnce("core/queries/UserPermissionQueries.php");
includeOnce("core/queries/BookCategoryQueries.php");
includeOnce("core/queries/BookVolumeQueries.php");

class BookQueries extends Queries
{
    public static function queryAllActiveOrderedByCategory($latestFirst = false, $fetch = false)
    {
        $permissionAccessPrivateBook = UserPermissionType::AccessPrivateBook;
        $user = AccountUtils::isLoggedIn() ? AccountUtils::currentUser() : null;
        if ($user !== null && $user->hasPreference(UserPreference::ProtectionMode)) {
            $user = null;
        }
        if ($user !== null && $user->hasPermission(GeneralUserPermission::PrivateBook)) {
            $sql = "SELECT b.* FROM Book b WHERE b.status = 1 ";
        } else {
            if ($user !== null) {
                $sql = "SELECT b.* FROM Book b LEFT JOIN UserPermission up ON (b.id = up.source_id AND up.type = $permissionAccessPrivateBook AND up.status = 1 AND up.user_id = $user->id) WHERE b.status = 1 AND (b.is_private = 0 OR up.id IS NOT NULL) ";
            } else {
                $sql = "SELECT b.* FROM Book b WHERE b.status = 1 AND b.is_private = 0 ";
            }
        }
        $orderClause = $latestFirst ? "DESC" : "ASC";
        $sql .= "ORDER BY b.book_category_id, b.date_added $orderClause;";
        return static::queryBySql($sql);
    }

    public static function queryActiveBySearchName($searchName)
    {
        $searchName = StringUtils::toSearchName($searchName);
        $permissionAccessPrivateBook = UserPermissionType::AccessPrivateBook;
        $user = AccountUtils::isLoggedIn() ? AccountUtils::currentUser() : null;
        if ($user !== null && $user->hasPreference(UserPreference::ProtectionMode)) {
            $user = null;
        }
        if ($user !== null && $user->hasPermission(GeneralUserPermission::PrivateBook)) {
            $sql = "SELECT b.* FROM Book b WHERE b.search_name = ? AND b.status = 1 LIMIT 1;";
        } else {
            if ($user !== null) {
                $sql = "SELECT b.* FROM Book b LEFT JOIN UserPermission up ON (b.id = up.source_id AND up.type = $permissionAccessPrivateBook AND up.status = 1 AND up.user_id = $user->id) WHERE b.search_name = ? AND b.status = 1 AND (b.is_private = 0 OR up.id IS NOT NULL) ";
            } else {
                $sql = "SELECT b.* FROM Book b WHERE b.search_name = ? AND b.status = 1 AND b.is_private = 0 ";
            }
        }
        $result = static::queryBySql($sql, array($searchName));
        if (count($result) > 0) {
            return $result[0];
        }
        return null;
    }

    public static function queryActiveById($id)
    {
        $id = StringUtils::cleanNumber($id);
        $permissionAccessPrivateBook = UserPermissionType::AccessPrivateBook;
        $user = AccountUtils::isLoggedIn() ? AccountUtils::currentUser() : null;
        if ($user !== null && $user->hasPreference(UserPreference::ProtectionMode)) {
            $user = null;
        }
        if ($user !== null && $user->hasPermission(GeneralUserPermission::PrivateBook)) {
            $sql = "SELECT b.* FROM Book b WHERE b.id = ? AND b.status = 1 LIMIT 1;";
        } else {
            if ($user !== null) {
                $sql = "SELECT b.* FROM Book b LEFT JOIN UserPermission up ON (b.id = up.source_id AND up.type = $permissionAccessPrivateBook AND up.status = 1 AND up.user_id = $user->id) WHERE b.id = ? AND b.status = 1 AND (b.is_private = 0 OR up.id IS NOT NULL);";
            } else {
                $sql = "SELECT b.* FROM Book b WHERE b.id = ? AND b.status = 1 AND b.is_private = 0;";
            }
        }
        $result = static::queryBySql($sql, array($id));
        if (count($result) > 0) {
            return $result[0];
        }
        return null;
    }

    public static function queryByCategoryId($categoryId)
    {
        $categoryId = StringUtils::cleanNumber($categoryId);
        $permissionAccessPrivateBook = UserPermissionType::AccessPrivateBook;
        $user = AccountUtils::isLoggedIn() ? AccountUtils::currentUser() : null;
        if ($user !== null && $user->hasPreference(UserPreference::ProtectionMode)) {
            $user = null;
        }
        if ($user !== null && $user->hasPermission(GeneralUserPermission::PrivateBook)) {
            $sql = "SELECT b.* FROM Book b WHERE b.book_category_id = ? AND b.status = 1 LIMIT 1;";
        } else {
            if ($user !== null) {
                $sql = "SELECT b.* FROM Book b LEFT JOIN UserPermission up ON (b.id = up.source_id AND up.type = $permissionAccessPrivateBook AND up.status = 1 AND up.user_id = $user->id) WHERE b.book_category_id = ? AND b.status = 1 AND (b.is_private = 0 OR up.id IS NOT NULL) ";
            } else {
                $sql = "SELECT b.* FROM Book b WHERE b.book_category_id = ? AND b.status = 1 AND b.is_private = 0 ";
            }
        }
        $sql .= " ORDER BY date_added ASC;";
        return static::queryBySql($sql, array($categoryId));
    }

    public static function queryAllActiveWithLimit($offset, $limit, $categoryIds = array())
    {
        $permissionAccessPrivateBook = UserPermissionType::AccessPrivateBook;
        $user = AccountUtils::isLoggedIn() ? AccountUtils::currentUser() : null;
        if ($user !== null && $user->hasPreference(UserPreference::ProtectionMode)) {
            $user = null;
        }
        $offset = StringUtils::cleanNumber($offset);
        $limit = StringUtils::cleanNumber($limit);
        $categoryIds = StringUtils::cleanArrayNumber($categoryIds);
        $categoryIdsParam = "";
        if (count($categoryIds) > 0) {
            $categoryIdsCsv = implode(",", $categoryIds);
            $categoryIdsParam = " AND b.book_category_id in ($categoryIdsCsv) ";
        }
        if ($user !== null && $user->hasPermission(GeneralUserPermission::PrivateBook)) {
            $sql = "SELECT b.* FROM Book b WHERE b.status = 1 $categoryIdsParam ";
        } else {
            if ($user !== null) {
                $sql = "SELECT b.* FROM Book b LEFT JOIN UserPermission up ON (b.id = up.source_id AND up.type = $permissionAccessPrivateBook AND up.status = 1 AND up.user_id = $user->id) WHERE b.status = 1 $categoryIdsParam AND (b.is_private = 0 OR up.id IS NOT NULL) ";
            } else {
                $sql = "SELECT b.* FROM Book b WHERE b.status = 1 $categoryIdsParam AND b.is_private = 0 ";
            }
        }
        $sql .= "ORDER BY b.date_added DESC, b.id DESC, b.book_category_id ";
        if ($limit > 0) {
            $sql .= "LIMIT $limit ";
        }
        if ($offset > 0) {
            $sql .= "OFFSET $offset ";
        }
        return static::queryBySql($sql);
    }
    public static function queryByIds($ids, $categoryIds = array())
    {
        if (count($ids) == 0) {
            return array();
        }
        $ids = StringUtils::cleanArrayNumber($ids);
        $csv = implode(",", $ids);
        $categoryIds = StringUtils::cleanArrayNumber($categoryIds);
        $categoryIdsParam = "";
        if (count($categoryIds) > 0) {
            $categoryIdsCsv = implode(",", $categoryIds);
            $categoryIdsParam = " AND b.book_category_id in ($categoryIdsCsv) ";
        }
        $permissionAccessPrivateBook = UserPermissionType::AccessPrivateBook;
        $user = AccountUtils::isLoggedIn() ? AccountUtils::currentUser() : null;
        if ($user !== null && $user->hasPreference(UserPreference::ProtectionMode)) {
            $user = null;
        }
        if ($user !== null && $user->hasPermission(GeneralUserPermission::PrivateBook)) {
            $sql = "SELECT b.* FROM Book b WHERE b.id in ($csv) AND b.status = 1 $categoryIdsParam ";
        } else {
            if ($user !== null) {
                $sql = "SELECT b.* FROM Book b LEFT JOIN UserPermission up ON (b.id = up.source_id AND up.type = $permissionAccessPrivateBook AND up.status = 1 AND up.user_id = $user->id) WHERE b.id in ($csv) AND b.status = 1 $categoryIdsParam AND (b.is_private = 0 OR up.id IS NOT NULL) ";
            } else {
                $sql = "SELECT b.* FROM Book b WHERE b.id in ($csv) AND b.status = 1 $categoryIdsParam AND b.is_private = 0 ";
            }
        }
        $sql .= " ORDER BY FIELD(b.id, $csv);";
        return static::queryBySql($sql);
    }

    public static function updateSearchNames()
    {
        static::_updateSearchNames("name", "searchName");
    }


    public static function queryWithInvalidThumbnails($limit = 10)
    {
        $config = Config::getInstance();
        $sql = "SELECT * FROM Book WHERE (thumb_url is null OR (thumb_url not like '$config->STATIC_IMAGES_BASE%' AND thumb_url not like '%-thumb.jpg') OR (image_ref not like '$config->STATIC_IMAGES_BASE%' AND image_ref not like '%-s.jpg')) AND ((thumb_url is not null AND thumb_url != '') OR (image_ref is not null AND image_ref != '')) order by image_ref desc, thumb_url desc LIMIT $limit";
        return static::queryBySql($sql);
    }

    public static function queryNewBookCount()
    {
        $config = Config::getInstance();
        $sql = "SELECT count(*) as value FROM Book WHERE status = ? AND is_private = 0 AND DATEDIFF(CURRENT_DATE, date_added) <= ?";

        $newBooksCount = BookQueries::querySimpleSql($sql, array(1, $config->BOOK_NEW_TAG_DAYS));

        return array("nbc" => (int)$newBooksCount[0]["value"]);
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
            $categoryIdsParam = "AND b.book_category_id in ($categoryIdsCsv)";
        }
        $sql = "SELECT b.* FROM Book b WHERE (b.label_type | 1) = b.label_type $categoryIdsParam AND b.status = 1 AND b.is_private = 0 ORDER BY b.label_mark_date DESC LIMIT $limit";
        return static::queryBySql($sql);
    }

    public static function queryActiveForRSS()
    {
        return static::queryBySql("SELECT b.* FROM Book b WHERE b.is_private = 0 AND b.status = 1 ORDER BY b.id DESC LIMIT 20;");
    }

    public static function queryActiveByCategoryIdForRSS($categoryId)
    {
        $categoryId = StringUtils::cleanNumber($categoryId);
        return static::queryBySql("SELECT b.* FROM Book b WHERE b.book_category_id = ? AND b.is_private = 0 AND b.status = 1 ORDER BY b.id DESC LIMIT 20;", array($categoryId));
    }

    public static function queryLabelTypeByIds($ids = array())
    {
        $ids = StringUtils::cleanArrayNumber($ids);
        $csv = implode(",", $ids);
        return static::querySimpleSql("SELECT b.id, b.date_added, b.label_type FROM Book b WHERE b.id IN ($csv);");
    }
}
