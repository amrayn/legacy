<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("core/queries/Queries.php");
includeOnce("core/models/User.php");
includeOnce("core/utils/StringUtils.php");

class UserQueries extends Queries
{
    public static function queryByEmail($email)
    {
        $email = StringUtils::cleanAlphaNumber($email, array(".", "_", "@", "-"));
        $user = static::queryActiveByFields(array("email" => strtolower($email)), true);
        if ($user === null) {
            $user = UserQueries::queryByUserId($email);
        }
        return $user;
    }

    public static function queryByUserId($userId)
    {
        $userId = StringUtils::cleanAlphaNumber($userId, array("-", ".", "_"));
        $result = UserQueries::queryByUserIds(array($userId));
        if (count($result) == 0) {
            return null;
        }
        return $result[0];
    }

    public static function queryByUserIds($ids)
    {
        $idsWithQuotes = array();
        foreach ($ids as $id) {
            $userId = StringUtils::cleanAlphaNumber($id, array("-", ".", "_"));
            if (strlen(trim($userId)) > 0) {
                $idsWithQuotes[] = "'$userId'";
            }
        }
        if (count($idsWithQuotes) == 0) {
            return array();
        }
        $csv = strtolower(implode(",", $idsWithQuotes));
        $cleanIds = StringUtils::cleanAlphaNumber($csv, array("-", ".", "_", ","));
        $sql = "SELECT * FROM User WHERE user_id IS NOT NULL AND lower(user_id) in ($csv) AND status = 1 ORDER BY id";
        return static::queryBySql($sql, array());
    }

    public static function queryByGeneralUserPermission($userPermission)
    {
        $userPermissionFlag = GeneralUserPermission::getFlag($userPermission);
        $requiredFurtherPermissionFlag =  GeneralUserPermission::getProp($userPermission, "requiredPermission");
        $userPermissionFlag = $requiredFurtherPermissionFlag === null ? $userPermissionFlag : $userPermissionFlag | $requiredFurtherPermissionFlag;
        $result = static::queryBySql(static::selectAll() . " WHERE permission_flag & ?;", array($userPermissionFlag));
        return $result;
    }

    public static function queryByUserPreference($userPreference)
    {
        $userPreferenceFlag = UserPreference::getFlag($userPreference);
        $result = static::queryBySql(static::selectAll() . " WHERE preference_flag & ?;", array($userPreferenceFlag));
        return $result;
    }

    public static function queryByUserPreferenceAndGeneralUserPermission($userPreference, $userPermission)
    {
        $userPreferenceFlag = UserPreference::getFlag($userPreference);
        $userPermissionFlag = GeneralUserPermission::getFlag($userPermission);
        $requiredFurtherPermissionFlag =  GeneralUserPermission::getProp($userPermission, "requiredPermission");
        $userPermissionFlag = $requiredFurtherPermissionFlag === null ? $userPermissionFlag : $userPermissionFlag | $requiredFurtherPermissionFlag;
        $superUserFlag = GeneralUserPermission::getFlag(GeneralUserPermission::SuperUser);
        return static::queryBySql(static::selectAll() . " WHERE preference_flag & ? and (permission_flag & ? OR permission_flag & ?);", array($userPreferenceFlag, $userPermissionFlag, $superUserFlag));
    }

    public static function queryByFuzzyTerm($term)
    {
        return static::queryBySql(static::selectAll() . " WHERE upper(email) like ? OR upper(name) like ? OR user_id = ? ORDER BY status DESC;", array(strtoupper("%$term%"), strtoupper("%$term%"), strtoupper("$term")));
    }

    public static function queryByUserIdsAndUserPreference($userPreference, $idList = array())
    {
        if (count($idList) == 0) {
            return array();
        }
        $list = StringUtils::cleanArrayNumber($idList);
        $userPreferenceFlag = UserPreference::getFlag($userPreference);
        return static::queryBySql(static::selectAll() . " WHERE id IN (" . implode(",", $list) . ") AND preference_flag & ?", array($userPreferenceFlag));
    }
}
