<?php

includeOnce("core/models/BaseModel.php");

class UploadedMedia extends BaseModel
{
    public function fields()
    {
        return array_merge(parent::fields(),  array(
            "type",
            "length",
            "fileSize",
            "url",
            "userId"));
    }
}

?>
