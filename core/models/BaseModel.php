<?php

includeOnce("core/utils/StringUtils.php");
includeOnce("core/utils/DateUtils.php");

abstract class BaseEnum
{
    public static function getFlag($enum)
    {
        return BaseEnum::getProp($enum, "flag");
    }
    public static function getProp($enum, $prop)
    {
        $enumObj = json_decode($enum);
        if (property_exists($enumObj, $prop)) {
            return $enumObj->$prop;
        }
        return null;
    }
}
abstract class BaseModel
{
    // $id, $status, $date_added, $last_updated

    public function __construct($fieldsArray = array())
    {
        foreach ($fieldsArray as $key => $value) {
            $keyName = trim(StringUtils::underscoreToCamel($key));
            if (is_string($value) && strlen($value) == 0) {
                $this->$keyName = null;
            } else {
                $this->$keyName = $value;
            }
        }
    }

    public function getPublicId()
    {
        if ($this->id === null) {
            return null;
        }
        $baseId = get_called_class() . "-$this->id";
        $currentId = StringUtils::encryptText($baseId);
        return StringUtils::encryptedTextAsPublicId($currentId);
    }

    public function fetchObjects()
    {
        // Ignore as nothing to be fetched
    }

    public static function getActualId($publicId)
    {
        $normalizePublicId = StringUtils::normalizePublicId($publicId);
        $decrypted = StringUtils::decryptText($normalizePublicId);
        $classInPublicId = substr($decrypted, 0, strpos($decrypted, "-"));
        $actualClass = get_called_class();
        if ($classInPublicId != $actualClass) {
            return -1;
        }
        $id = substr($decrypted, strlen(get_called_class()) + 1);
        if (trim($id) === "") {
            return -1;
        }
        return (int)$id;
    }

    /**
     * Use script tools/convert-db-fields-to-fields-method.php to generate for child classes
     */
    public function fields()
    {
        // DO NOT INCLUDE ID AS WE NEVER WANT TO INSERT MANUALLY OR UPDATE AN ID
        return array("status", "dateAdded", "lastUpdated");
    }

    public function isNew($maxDays)
    {
        return DateUtils::daysDiff(DateUtils::newDateUtc($this->dateAdded), DateUtils::newDateUtc()) <= $maxDays;
    }

    public function localLastUpdated()
    {
        return DateUtils::toLocalDate(DateUtils::newDateUtc($this->lastUpdated));
    }

    public function localDateAdded()
    {
        return DateUtils::toLocalDate(DateUtils::newDateUtc($this->dateAdded));
    }

    public function displayableDateAdded()
    {
        return DateUtils::displayableTime($this->localDateAdded());
    }

    public function displayableLastUpdated()
    {
        return DateUtils::displayableTime($this->localLastUpdated());
    }

    public function isActive()
    {
        return $this->status == 1;
    }
}
