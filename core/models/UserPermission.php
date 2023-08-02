<?php

includeOnce("core/models/BaseModel.php");


abstract class UserPermissionType
{
    const EditAudio = 1;
    const EditSeries = 2;
    const EditSpeaker = 3;
    const AccessPrivateAudio = 4;
    const AccessPrivateAudioSeries = 5;
    const AccessPrivateBook = 6;
    const EditBook = 7;
    const AddAudio = 8; // Only able to add audio / series to speaker / series and edit their own contents
}

class UserPermission extends BaseModel
{
    public function fields()
    {
        return array_merge(parent::fields(),  array(
            "sourceId",
            "type",
            "userId"));
    }
}

?>
