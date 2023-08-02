<?php

includeOnce("core/models/BaseModel.php");

class Ayah extends BaseModel
{
    public function fields()
    {
        return array_merge(parent::fields(),  array(
            "tags",
            "text",
            "ayah",
            "surah",
            "databaseId"));
    }
}

?>
