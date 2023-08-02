<?php

includeOnce("core/models/BaseModel.php");

class AudioCategory extends BaseModel
{

    public function fields()
    {
        return array_merge(parent::fields(),  array(
            "name",
            "description",
            "searchName"));
    }
  
}
