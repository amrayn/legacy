<?php

includeOnce("core/models/BaseModel.php");
includeOnce("core/utils/StringUtils.php");

class BookVolume extends BaseModel
{
    public function fields()
    {
        return array_merge(parent::fields(), array(
            "pages",
            "thumbUrl",
            "imageRef",
            "sortOrder",
            "searchName",
            "year",
            "isbn",
            "fileSize",
            "url",
            "description",
            "name",
            "bookId"));
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

    public function resolveThumbnail()
    {
        $thumbnailUrl = $this->thumbUrl;
        if (strlen($thumbnailUrl) == 0) {
            $config = Config::getInstance();
            $thumbnailUrl = "https://amrayn.com/assets/images/profiles/default-book-volume-image.png";
        }
        return $thumbnailUrl;
    }

    public function safeThumbnail($thumb = true)
    {
        return $this->resolveThumbnail() . "?" . ($thumb ? "thumb&" : "") . "v=$config->IMG_VERSION&bv=$config->BOOK_VERSION";
        /*
            $config = Config::getInstance();
            $publicId = $this->getPublicId();
            return "/books/thumbv/$publicId?" . ($thumb ? "thumb&" : "") . "v=$config->IMG_VERSION&bv=$config->BOOK_VERSION";*/
    }
}
