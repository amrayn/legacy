<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("core/database/DB.php");
includeOnce("core/utils/StringUtils.php");
includeOnce("core/queries/QueriesBank.php");

class Queries
{
    public static function queryNonStandardSql($sql, $nonObjFields = array(), $objKey = "obj")
    {
        $queryResult = static::querySimpleSql($sql);
        $result = array();
        $i = 0;
        foreach ($queryResult as $field) {
            $instance = static::newInstance();
            foreach ($field as $key => $value) {
                if (!in_array($key, $nonObjFields)) {
                    $keyName = trim(StringUtils::underscoreToCamel($key));
                    $instance->$keyName = $value;
                }
            }
            $instance->fetchObjects();
            $result[$i] = array();
            $result[$i][$objKey] = $instance;
            foreach ($nonObjFields as $col) {
                $result[$i][$col] = $field[$col];
            }
            $i++;
        }
        return $result;
    }

    protected static function setObjectFromQueryResult($field, &$obj)
    {
        foreach ($field as $key => $value) {
            $keyName = trim(StringUtils::underscoreToCamel($key));
            if ($keyName !== "" && !is_numeric($keyName)) {
                $obj->$keyName = $value;
            }
        }
    }

    public static function persist(&$instance, $onlyUpdate = array(), $ignorePrivacy = false)
    {
        if ($instance === null) {
            Debug::log("Failed to persist null [" . static::me() . "]");

            return false;
        }
        $lastUpdatedRef = null;
        if (!isset($instance->id) || $instance->id === null || static::queryById($instance->id, $ignorePrivacy) === null) {
            Debug::log("Treating as INSERT statement; instance =>");
            Debug::log($instance);
            $statementElements = static::insertStatement($instance, $lastUpdatedRef);
        } else {
            $statementElements = static::updateStatement($instance, $onlyUpdate, $lastUpdatedRef);
        }
        $db = DB::getInstance();
        $newId = $db->execute($statementElements["sql"], $statementElements["params"]);
        if ($newId === null) {
            return null;
        }
        $instance->id = $newId;
        $instance->lastUpdated = $lastUpdatedRef;
        return $instance;
    }

    public static function insertStatement(&$instance, &$lastUpdated = null)
    {
        $vars = (get_object_vars($instance));
        $fields = $instance->fields();
        $sql = "INSERT INTO " . static::me() . "(";
        $dateAddedForDB = DateUtils::newDateStrUtc();
        $lastUpdatedForDB = DateUtils::newDateStrUtc();

        foreach ($vars as $var => $val) {
            if (in_array($var, $fields)) {
                $inserts[] =  "`" . StringUtils::camelToUnderscore($var) . "`";
            }
        }
        $inserts[] = "`" . StringUtils::camelToUnderscore("lastUpdated") . "`";
        $inserts[] = "`" . StringUtils::camelToUnderscore("dateAdded") . "`";
        $sql .= implode(",", $inserts);
        $sql .= ") VALUES (";
        $inserts = array();
        foreach ($vars as $var => $val) {
            if ($var == "lastUpdatedOverride" && $val !== null) {
                $lastUpdatedForDB = $val;
            }
            if (in_array($var, $fields)) {
                $inserts[] = "?";
                $params[] = $val;
            }
        }
        $inserts[] = "?"; // lastUpdated
        $inserts[] = "?"; // dateAdded
        $params[] = $lastUpdatedForDB; // lastUpdated
        $params[] = $dateAddedForDB; // dateAdded
        $lastUpdated = $lastUpdatedForDB;
        $sql .= implode(",", $inserts);
        $sql .= ");";
        return array("sql" => $sql, "params" => $params);
    }

    public static function updateStatement(&$instance, $onlyUpdate = array(), &$lastUpdated = null)
    {
        $vars = (get_object_vars($instance));
        $fields = $instance->fields();
        $params = array();
        $sql = "UPDATE " . static::me() . " SET ";
        $lastUpdatedForDB = DateUtils::newDateStrUtc();

        $updates = array();
        foreach ($vars as $var => $val) {
            if ($var == "lastUpdatedOverride" && $val !== null) {
                $lastUpdatedForDB = $val;
            }
            if (count($onlyUpdate) > 0) {
                if (in_array($var, $onlyUpdate) && in_array($var, $fields)) {
                    $updates[] = "`" . StringUtils::camelToUnderscore($var) . "` = ? ";
                    $params[] = $val;
                }
            } else {
                if (in_array($var, $fields)) {
                    $updates[] = "`" . StringUtils::camelToUnderscore($var) . "` = ? ";
                    $params[] = $val;
                }
            }
        }
        $updates[] = "`" . StringUtils::camelToUnderscore("lastUpdated") . "` = ? ";
        $params[] = $lastUpdatedForDB;
        $lastUpdated = $lastUpdatedForDB;
        $sql .= implode(",", $updates);
        $sql .= " WHERE id = ?;";
        $params[] = $instance->id;
        return array("sql" => $sql, "params" => $params);
    }

    public static function executeSimple($sql)
    {
        $db = DB::getInstance();
        return $db->execute($sql);
    }

    public static function querySimpleSql($sql, $params = array())
    {
        $db = DB::getInstance();
        return $db->query($sql, $params);
    }

    public static function queryBySql($sql, $params = array())
    {
        $db = DB::getInstance();
        $queryResult = $db->query($sql, $params);
        $result = array();
        if ($queryResult != null) {
            foreach ($queryResult as $field) {
                $obj = static::newInstance();
                static::setObjectFromQueryResult($field, $obj);
                $obj->fetchObjects();
                $result[] = $obj;
            }
        }
        return $result;
    }

    protected static function selectAll()
    {
        return "SELECT * FROM " . static::me() . " ";
    }

    protected static function me()
    {
        return substr(get_called_class(), 0, -7); // {class_name}Queries
    }

    protected static function newInstance()
    {
        $entity = static::me();
        return new $entity();
    }

    public static function queryAllActive($latestFirst = false, $limit = null)
    {
        return static::queryBySql(static::selectAll() . " WHERE status = ? ORDER BY id " . ($latestFirst ? "DESC" : "ASC") . ($limit == null ? "" : " LIMIT $limit") . ";", array(1));
    }

    public static function queryAll()
    {
        return static::queryBySql(static::selectAll() . ";", array());
    }

    public static function queryActiveById($id)
    {
        $id = StringUtils::cleanNumber($id);
        return static::queryActiveByFields(array("id" => $id), true);
    }

    public static function queryById($id, $ignorePrivacy = false)
    {
        $id = StringUtils::cleanNumber($id);
        return static::queryByFields(array("id" => $id), true);
    }

    public static function hardDeleteById($id)
    {
        $id = StringUtils::cleanNumber($id);
        static::executeSimple("DELETE FROM " . static::me() . " WHERE id = $id");
    }

    public static function hardDeleteByIds($ids)
    {
        $ids = StringUtils::cleanArrayNumber($ids);
        if (count($ids) > 0) {
            $csv = implode(",", $ids);
            static::executeSimple("DELETE FROM " . static::me() . " WHERE id in ($csv)");
        }
    }

    public static function keyValueToParamsAndFilters($keyValue)
    {
        $params = array();
        $filters = array();
        foreach ($keyValue as $key => $value) {
            $filters[] = " `$key` = ? ";
            $params[] = $value;
        }
        return array("params" => $params, "filters" => $filters);
    }

    public static function queryByFields($keyValue, $isSingleResult = false, $latestFirst = false, $connector = "AND")
    {
        $paramsAndFilters = static::keyValueToParamsAndFilters($keyValue);
        $whereClause = implode(" $connector ", $paramsAndFilters["filters"]);

        $result = static::queryBySql(static::selectAll() . " WHERE $whereClause ORDER BY id " . ($latestFirst ? "DESC" : "ASC") . ";", $paramsAndFilters["params"]);
        if ($isSingleResult) {
            if (count($result) > 0) {
                return $result[0];
            }
            return null;
        }
        return $result;
    }

    public static function queryActiveByFields($keyValue, $isSingleResult = false, $latestFirst = false, $connector = "AND")
    {
        return static::queryByFields(array_merge($keyValue, array("status" => 1)), $isSingleResult, $latestFirst, $connector);
    }

    protected static function _updateSearchNames($nameField, $searchNameField)
    {
        $all = static::queryBySql(static::selectAll() . " WHERE status = ? AND (search_name = '' OR search_name IS NULL) LIMIT 100;", array(1));
        foreach ($all as &$obj) {
            if (StringUtils::toSearchName($obj->$nameField) != $obj->$searchNameField) {
                $obj->$searchNameField = StringUtils::toSearchName($obj->$nameField);
                static::persist($obj, array($searchNameField), true);
            }
        }
    }
}
