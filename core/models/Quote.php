<?php

includeOnce("core/models/BaseModel.php");

class Quote extends BaseModel
{
    public function fields()
    {
        return array_merge(parent::fields(),  array(
            "link",
            "ref",
            "text",
            "category",
            "type"));
    }
}

?>
