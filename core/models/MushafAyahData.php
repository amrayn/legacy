<?php

includeOnce("core/models/BaseModel.php");

abstract class MushafAyahDataTypes
{
	const PageNumber = 1;
	const Coordinates = 2;
}

class MushafAyahData extends BaseModel
{
    public function fields()
    {
        return array_merge(parent::fields(),  array(
            "type",
            "surah",
            "ayah",
            "data"));
    }
}
