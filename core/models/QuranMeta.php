<?php

includeOnce("core/models/BaseModel.php");

abstract class QuranMetaTypes
{
	const Original = 1;
	const Translation = 2;
	const Transliteration = 3;
	const Tafsir = 4;
}

class QuranMeta extends BaseModel
{
    public function fields()
    {
        return array_merge(parent::fields(),  array(
            "fontSize",
            "numberingStyle",
            "cssClass",
            "direction",
            "name",
            "type"));
    }
}
