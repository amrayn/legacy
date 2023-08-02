<?php

includeOnce("core/models/BaseModel.php");

abstract class SurahInfoTypes
{
	const VerseCount = 1;
	const RevelationPeriod = 2;
	const ManzilSurah = 3;
	const HizbList = 4;
	const SurahRukuhList = 5;
	const AyahSajdah = 6;
	const JuzSurah = 7;
	const SurahName = 8;
	const SurahNameMeaning = 9;
	const SurahNameEnglish = 10;
	const SurahNameEnglishPronounced = 11;
	const RevelationOrder = 12;
	const NumberOfPagesInMedinaMushaf = 13;
	const IntroDetails = 14;
}

class SurahInfo extends BaseModel
{
    public function fields()
    {
        return array_merge(parent::fields(),  array(
            "info",
            "surah",
            "type"));
    }

	public function asArray()
	{
		return explode(",", $this->info);
	}


	public static function getInfoByType($type, &$surahInfoResults, $getValue = false) {
		if (!is_array($surahInfoResults) && $surahInfoResults->type == $type) {
			return $getValue ? $surahInfoResults->info : $surahInfoResults;
		} else if (is_array($surahInfoResults)) {
			foreach ($surahInfoResults as $surahInfo) {
				if ($surahInfo->type == $type) {
					return $getValue ? $surahInfo->info : $surahInfo;
				}
			}
		}
		return null;
	}
	public static function getInfoByTypeAndSurah($type, $surah, &$surahInfoResults, $getValue = false) {
		if (!is_array($surahInfoResults) && $surahInfoResults->type == $type && $surahInfoResults->surah == $surah) {
			return $getValue ? $surahInfoResults->info : $surahInfoResults;
		} else if (is_array($surahInfoResults)) {
			foreach ($surahInfoResults as $surahInfo) {
				if ($surahInfo->type == $type && $surahInfo->surah == $surah) {
					return $getValue ? $surahInfo->info : $surahInfo;
				}
			}
		}
		return null;
	}
}
