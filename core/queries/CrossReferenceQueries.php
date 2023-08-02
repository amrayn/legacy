<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("core/queries/Queries.php");
includeOnce("core/models/CrossReference.php");

class CrossReferenceQueries extends Queries
{
    public static function queryByTypeId($type, $typeId)
    {
        $type = StringUtils::cleanNumber($type);
        $typeId = StringUtils::cleanNumber($typeId);
        return static::queryActiveByFields(array("type" => $type, "type_id" => $typeId));
    }
    public static function deleteForTypeId($type, $typeId)
    {
        $type = StringUtils::cleanNumber($type);
        $typeId = StringUtils::cleanNumber($typeId);
        static::executeSimple("DELETE FROM CrossReference WHERE type = $type AND type_id = $typeId");
    }
}
