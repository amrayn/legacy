<?php

includeOnce("core/models/BaseModel.php");
includeOnce("core/models/Hadith.php");

class HadithNote extends BaseModel
{
    public function fields()
    {
        return array_merge(parent::fields(),  array(
            "text",
            "title",
            "userId",
            "hadithNumber",
            "volumeNumber",
            "bookNumber",
            "collectionId"));
    }}
?>
