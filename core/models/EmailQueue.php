<?php

includeOnce("core/models/BaseModel.php");

abstract class EmailPriority
{
    const Urgent = 1;
    const High = 2;
    const Medium = 3;
    const Normal = 4;
}

class EmailQueue extends BaseModel
{

    public function fields()
    {
        return array_merge(parent::fields(), array(
            "fromAddress",
            "fromName",
            "toAddress",
            "toName",
            "replyToAddress",
            "replyToName",
            "subject",
            "text",
            "htmlText",
            "attachPath",
            "sentDate",
            "priority",
            "type"));

    }
}

?>
