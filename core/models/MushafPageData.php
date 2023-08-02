<?php

includeOnce("core/models/BaseModel.php");

class MushafPageData extends BaseModel
{
    public function fields()
    {
        return array_merge(parent::fields(),  array(
            "ayahRef",
            "page"));
    }
}
