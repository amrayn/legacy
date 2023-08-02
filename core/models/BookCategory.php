<?php

includeOnce("core/models/BaseModel.php");

class BookCategory extends BaseModel
{

    public function fields()
    {
        return array_merge(parent::fields(),  array(
            "searchName",
            "description",
            "name"));
    }
  
}
