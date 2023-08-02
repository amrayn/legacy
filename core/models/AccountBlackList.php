<?php

includeOnce("core/models/BaseModel.php");


class AccountBlackList extends BaseModel
{

    public function fields()
    {
        return array_merge(parent::fields(),  array(
            "email"));
    }
}

?>
