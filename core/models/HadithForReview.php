<?php

includeOnce("core/models/BaseModel.php");
includeOnce("core/models/Hadith.php");

class HadithForReview extends BaseModel
{
    public function fields()
    {
        return array_merge(parent::fields(),  array(
            "negativeReviews",
            "positiveReviews",
            "newLinks",
            "newGradeFlag",
            "newTags",
            "newReferences",
            "newArabicText",
            "newText",
            "hadithRef",
            "userId"));
    }

	public function hadithLink() 
	{
		return Hadith::referenceFromLink($this->hadithRef, "short");
	}
	public function hadithName() 
	{
		return Hadith::referenceFromLink($this->hadithRef, "long");
	}
}
?>
