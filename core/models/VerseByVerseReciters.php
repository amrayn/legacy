<?php

includeOnce("core/models/BaseModel.php");

class VerseByVerseReciters extends BaseModel
{
    public function fields()
    {
        return array_merge(parent::fields(),  array(
            "name",
            "urlPattern",
            "basmalaUrl"));
    }
}
