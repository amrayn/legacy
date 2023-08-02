<?php

includeOnce("core/models/BaseModel.php");

class ConfigData extends BaseModel
{
    public function fields()
    {
        return array_merge(parent::fields(),  array(
            "value",
            "key"));
    }}

?>
