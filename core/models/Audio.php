<?php

includeOnce("core/models/BaseModel.php");
includeOnce("core/queries/AudioCategoryQueries.php");
includeOnce("core/utils/StringUtils.php");

abstract class AudioTypes
{
    const Mp3 = 1;
    const YouTube = 2;
    const Mp4Video = 3;
    const FlvVideo = 4;
    const OgvVideo = 5;
    const SoundCloud = 6;
    const Vimeo = 7;
    const Mp4Audio = 8;
    const DailyMotion = 9;
    const WebMVideo = 10;
}

abstract class AudioLabelTypes
{
    const None = 0;
    const Featured = 1;
    const MustListen = 2;
}

class Audio extends BaseModel
{
    public function fields()
    {
        return array_merge(parent::fields(), array(
            "fileSize",
            "url",
            "views",
            "tags",
            "type",
            "isPrivate",
            "privateByParent",
            "labelType",
            "labelMarkDate",
            "convertToAudio",
            "length",
            "language",
            "name",
            "description",
            "imageRef",
            "thumbUrl",
            "speakerId",
            "audioSeriesId",
            "searchName",
            "sortOrderInSeries",
            "convertToAudio",
            "audioCategoryId"));
    }

    public function fetchObjects()
    {
        if ($this->audioCategoryId != null) {
            $this->audioCategory = AudioCategoryQueries::queryActiveById($this->audioCategoryId);
        }
    }

    public function isPartOfSeries()
    {
        return $this->audioSeriesId != 0;
    }

    public function hasAudioCategory()
    {
        return $this->audioCategoryId != null && isset($this->audioCategory) && $this->audioCategory != null;
    }

    public function isMp3()
    {
        return $this->type == AudioTypes::Mp3;
    }

    public function isYoutube()
    {
        return $this->type == AudioTypes::YouTube;
    }

    public function isDailyMotion()
    {
        return $this->type == AudioTypes::DailyMotion;
    }

    public function isVimeo()
    {
        return $this->type == AudioTypes::Vimeo;
    }

    public function isVideo()
    {
        return $this->type == AudioTypes::Mp4Video || $this->type == AudioTypes::FlvVideo || $this->type == AudioTypes::OgvVideo || $this->type == AudioTypes::WebMVideo;
    }

    public function isSoundCloud()
    {
        return $this->type == AudioTypes::SoundCloud;
    }

    public function getYoutubeVideoId()
    {
        if (!$this->isYoutube()) {
            return "";
        }
        if (!isset($this->youtubeVideoId)) {
            $arrayOfVars = array();
            parse_str(parse_url($this->url, PHP_URL_QUERY), $arrayOfVars);
            $this->youtubeVideoId = $arrayOfVars['v'];
        }
        return $this->youtubeVideoId;
    }

    public function getDailyMotionId()
    {
        if (!$this->isDailyMotion()) {
            return "";
        }
        if (!isset($this->dailyMotionId)) {
            $arrayOfPath = explode("/", $this->url);
            $this->dailyMotionId = $arrayOfPath[count($arrayOfPath) - 1];
        }
        return $this->dailyMotionId;
    }

    public function getSoundCloudId()
    {
        if (!$this->isSoundCloud()) {
            return "";
        }
        if (!isset($this->soundCloudId)) {
            $arrayOfPath = explode("/", $this->url);
            $this->soundCloudId = $arrayOfPath[count($arrayOfPath) - 1];
        }
        return $this->soundCloudId;
    }

    public function getVimeoId()
    {
        if (!$this->isVimeo()) {
            return "";
        }
        if (!isset($this->vimeoId)) {
            $arrayOfPath = explode("/", $this->url);
            $this->vimeoId = $arrayOfPath[count($arrayOfPath) - 1];
        }
        return $this->vimeoId;
    }

    public function determineLanguage()
    {
        return Utils::determineLanguageById($this->language);
    }

    public function httpUrl()
    {
        if (StringUtils::startsWith($this->url, "//")) {
            return "http:$this->url";
        }
        return $this->url;
    }

    public function httpsUrl()
    {
        if (StringUtils::startsWith($this->url, "//rc.")) {
            return "https:$this->url";
        }
        return $this->url;
    }

    public function isLocal()
    {
        return preg_match("/rc.amrayn.com\/audios\//", $this->url) === 1;
    }

    public function getLocal()
    {
        if ($this->isLocal()) {
          $checkStr = "rc.amrayn.com/audios/";
          return root("resources/audios/") . substr($this->url, strpos($this->url, $checkStr) + strlen($checkStr));
        }
        return null;
    }

    public function determineType()
    {
        if (strpos($this->url, "youtube.com") !== false || strpos($this->url, "youtu.be") !== false) {
            return AudioTypes::YouTube;
        } elseif (strpos($this->url, "soundcloud.com") !== false) {
            return AudioTypes::SoundCloud;
        } elseif (strpos($this->url, "vimeo.com") !== false) {
            return AudioTypes::Vimeo;
        } elseif (strpos($this->url, "dailymotion.com") !== false) {
            return AudioTypes::DailyMotion;
        } elseif (StringUtils::endsWith($this->url, ".mp4")) {
            return AudioTypes::Mp4Video;
        } elseif (StringUtils::endsWith($this->url, ".flv")) {
            return AudioTypes::FlvVideo;
        } elseif (StringUtils::endsWith($this->url, ".ogv")) {
            return AudioTypes::OgvVideo;
        } elseif (StringUtils::endsWith($this->url, ".webm")) {
            return AudioTypes::WebMVideo;
        }
        return AudioTypes::Mp3;
    }

    public function resolveThumbnail()
    {
        $config = Config::getInstance();
        $thumbnailUrl = $this->thumbUrl;

        if ($this->speakerId == 19 && is_numeric($this->sortOrderInSeries)) {
          $thumb = true;
          $thumbnailUrl = "https://amrayn.com/assets/images/profiles/Surah-$this->sortOrderInSeries-" . ($thumb ? "100x100-thumb" : "256x256-s") . ".jpg";
        }

        if ($this->isYouTube() && strlen($thumbnailUrl) == 0) {
            $thumbnailUrl = "//img.youtube.com/vi/" . $this->getYoutubeVideoId() . "/default.jpg";
        }
        if (strlen($thumbnailUrl) == 0) {
            $thumbnailUrl = "https://amrayn.com/assets/images/profiles/default-";
            if ($this->isYoutube()) {
                $thumbnailUrl .= "youtube";
            } elseif ($this->isVideo()) {
                $thumbnailUrl .= "video";
            } elseif ($this->isSoundCloud()) {
                $thumbnailUrl .= "soundcloud";
            } elseif ($this->isVimeo()) {
                $thumbnailUrl .= "vimeo";
            } else {
                $thumbnailUrl .= "audio";
            }
            $thumbnailUrl .= "-image.png?v=$config->IMG_VERSION";
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
