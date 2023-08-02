<?php

includeOnce("core/models/BaseModel.php");

includeOnce("core/utils/DateUtils.php");

class QuranStudies extends BaseModel
{

    public function fields()
    {
        return array_merge(parent::fields(),  array(
            "notes",
            "haveMistakes",
            "lastRevised",
            "hifzCompleted",
            "hifzStarted",
            "progress",
            "linesPerDay",
            "surah",
            "userId"));
    }

	public function isHifzCompleted() 
	{
		$result = $this->hifzStarted != null && $this->hifzCompleted != null;
		$result &= $this->progress == $this->totalAyahsInSurah;
		return $result;
	}

	public function localHifzStarted() 
	{
		if ($this->hifzStarted === null) return null;
		return DateUtils::toLocalDate(DateUtils::newDateUtc($this->hifzStarted));
	}

	public function localHifzCompleted() 
	{
		if ($this->hifzCompleted === null) return null;
		return DateUtils::toLocalDate(DateUtils::newDateUtc($this->hifzCompleted));
	}

	public function localLastRevised() 
	{
		if ($this->lastRevised === null) return null;
		return DateUtils::toLocalDate(DateUtils::newDateUtc($this->lastRevised));
	}

	public function calculateTargetDays($numberOfPages, $daysPerWeek = 7) 
	{
		$days = (15.0 / $this->linesPerDay) * $numberOfPages;
		$result = ($days / $daysPerWeek) * 7;
		return $result;
	}

	public function daysToComplete($numberOfPages, $daysPerWeek = 7) 
	{
		if ($this->hifzStarted === null) {
			return ceil($this->calculateTargetDays($numberOfPages, $daysPerWeek));
		}
		if ($this->isHifzCompleted()) {
			 return DateUtils::daysDiff($this->localHifzStarted(), $this->localHifzCompleted()) + 1 /* Inclusive */;
		}
		return ceil(($this->calculateTargetDays($numberOfPages, $daysPerWeek) - DateUtils::daysDiff($this->localHifzStarted(), DateUtils::newDate())) - 1);
	}
  
}
