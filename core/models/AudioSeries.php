<?php

includeOnce("core/models/BaseModel.php");
includeOnce("core/models/Audio.php");
includeOnce("core/queries/AudioCategoryQueries.php");

class AudioSeries extends BaseModel
{
    public function fields()
    {
        return array_merge(parent::fields(), array(
            "sitemapWritten",
            "isPrivate",
            "tags",
            "defaultDir",
            "legacySearchName",
            "searchName",
            "language",
            "audioCategoryId",
            "name",
            "description",
            "thumbUrl",
            "imageRef",
            "labelType",
            "labelMarkDate",
            "speakerId"));
    }

    public function fetchObjects()
    {
        if ($this->audioCategoryId != null) {
            $this->audioCategory = AudioCategoryQueries::queryActiveById($this->audioCategoryId);
        }
    }

    public function determineLanguage()
    {
        return Utils::determineLanguageById($this->language);
    }

    public function hasAudioCategory()
    {
        return $this->audioCategoryId != null && isset($this->audioCategory) && $this->audioCategory != null;
    }

    public function resolveThumbnail()
    {
        $thumbnailUrl = $this->thumbUrl;

        if (strlen($thumbnailUrl) == 0) {
            $config = Config::getInstance();
            $thumbnailUrl = "https://amrayn.com/assets/images/profiles/default-series-image.png?v=$config->IMG_VERSION";
        }
        return $thumbnailUrl;
    }

    public function featured()
    {
        return ($this->labelType | AudioLabelTypes::Featured) == $this->labelType;
    }

    public function mustListen()
    {
        return ($this->labelType | AudioLabelTypes::MustListen) == $this->labelType;
    }
}
