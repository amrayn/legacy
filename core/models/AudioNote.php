<?php

includeOnce("core/models/BaseModel.php");
includeOnce("core/utils/StringUtils.php");

class AudioNote extends BaseModel
{
    public function fields()
    {
        return array_merge(parent::fields(),  array(
            "userId",
            "isDefault",
            "audioId",
            "description",
            "name"));
    }
}
