<?php

includeOnce("core/models/BaseModel.php");


abstract class BookmarkTypes
{
    const Hadith = 1;
    const Audio = 2;
    const AudioSeries = 3;
    const Book = 4;
    const BookVolume = 5;
    const Blog = 6;
    const CurrentAudio = 7;
    const BlogReading = 8;
    const AudioSpeaker = 9;
}

class Bookmark extends BaseModel
{
    public function fields()
    {
        return array_merge(parent::fields(), array(
            "title",
            "userId",
            "type",
            "sourceId",
            "location"));
    }
    public function isValidLocation()
    {
        return ($this->type == BookmarkTypes::CurrentAudio || $this->type == BookmarkTypes::BlogReading) &&
            $this->location !== null && $this->location != "0" && $this->location != "" && $this->isNew(14);
    }
}
