<?php

includeOnce("core/models/BaseModel.php");

class HadithBookInfo extends BaseModel
{
    public function fields()
    {
        return array_merge(parent::fields(),  array(
            "bookName",
            "book",
            "volume",
            "collectionId"));
    }
}

?>
