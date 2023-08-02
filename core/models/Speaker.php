<?php

includeOnce("core/models/BaseModel.php");

abstract class SpeakerTypes
{
	const Collection = 1;
	const Scholar = 2;
	const StudentOfKnowledge = 3;
	const Speaker = 99;
	
	public static function order() {
		return array(
			SpeakerTypes::Collection,
			SpeakerTypes::Scholar,
			SpeakerTypes::StudentOfKnowledge,
			SpeakerTypes::Speaker
		);
	}
}

abstract class SpeakerFlags
{
	const None = 0;
}

class Speaker extends BaseModel
{
    public function fields()
    {
        return array_merge(parent::fields(),  array(
            "sortOrder",
            "flags",
            "type",
            "description",
            "defaultDir",
            "legacySearchName",
            "thumbUrl",
            "imageRef",
            "searchName",
            "name"));
    }

	public function isShowName() 
	{
		return $this->id != 14;
	}
	
	public function getTypeTitle() {
		switch ($this->type) {
			case SpeakerTypes::Scholar: return "Scholars";
			case SpeakerTypes::Collection: return "Collections";
			case SpeakerTypes::StudentOfKnowledge: return "Students Of Knowledge";
			default: return "More";
		}
	}

	public function isCollection() 
	{
		return $this->type == SpeakerTypes::Collection;
	}

    public function resolveThumbnail()
    {
        $thumbnailUrl = $this->thumbUrl;
        if (strlen($thumbnailUrl) == 0) {
            $config = Config::getInstance();
            $thumbnailUrl = "https://amrayn.com/assets/images/profiles/default-speaker-image.png?v=$config->IMG_VERSION";
        }
        return $thumbnailUrl;
    }
}

?>
