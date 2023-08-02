<?php

includeOnce("core/models/BaseModel.php");

class QuranWord extends BaseModel
{
    public function fields()
    {
        return array_merge(parent::fields(),  array(
            "meaning",
            "arabic",
            "wordId",
            "ayah",
            "surah",
            "databaseId"));
    }
}

?>
