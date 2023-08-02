<?php

includeOnce("core/models/BaseModel.php");

abstract class CrossReferenceType 
{
    const Hadith = 1;
}

class CrossReference extends BaseModel
{

    public function fields()
    {
        return array_merge(parent::fields(), array(
            "type",
            "typeId",
            "text",
            "link",
            ));

    }
}

?>
