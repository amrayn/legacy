<?php

includeOnce("core/models/BaseModel.php");
includeOnce("core/utils/StringUtils.php");

class AudioNoteItem extends BaseModel
{
    public function fields()
    {
        return array_merge(parent::fields(),  array(
            "sortOrder",
            "text",
            "time",
            "heading",
            "audioNoteId"));
    }
}
