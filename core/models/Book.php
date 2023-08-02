<?php

includeOnce("core/models/BaseModel.php");
includeOnce("core/utils/Utils.php");

abstract class BookLabelTypes
{
	const None = 0;
	const Featured = 1;
	const MustRead = 2;
}

class Book extends BaseModel
{

	// $name, $description, $imageRef, $author, $translator, $reviewer, $publisher, $pages, $bookCategoryId, $language, $hardCopyLink, $isbn, isPrivate, $labelType, $labelMarkDate

	public function fetchObjects()
	{
		if ($this->bookCategoryId != null) {
			$this->bookCategory = BookCategoryQueries::queryActiveById($this->bookCategoryId);
		}
		$this->bookVolumes = BookVolumeQueries::queryActiveByBookId($this->id);
	}

	public function determineLanguage()
	{
		return Utils::determineLanguageById($this->language);
	}

    public function fields()
    {
        return array_merge(parent::fields(),  array(
            "searchName",
            "isbn",
            "year",
            "hardCopyLink",
            "isPrivate",
            "labelType",
            "labelMarkDate",
            "defaultDir",
            "language",
            "bookCategoryId",
            "pages",
            "name",
            "description",
            "imageRef",
            "thumbUrl",
            "author",
            "translator",
            "reviewer",
            "publisher"));
    }

    public function resolveThumbnail()
    {
        $thumbnailUrl = $this->thumbUrl;
        if (strlen($thumbnailUrl) == 0) {
            $config = Config::getInstance();
            $thumbnailUrl = "https://amrayn.com/assets/images/profiles/default-book-image.jpg";
        }
        return $thumbnailUrl;
    }

	public function safeThumbnail($thumb = true) {
		return $this->resolveThumbnail() . "?" . ($thumb ? "thumb&" : "") . "v=$config->IMG_VERSION&bv=$config->BOOK_VERSION";
		/*
		$config = Config::getInstance();
		$publicId = $this->getPublicId();
		return "/books/thumb/$publicId?" . ($thumb ? "thumb&" : "") . "v=$config->IMG_VERSION&bv=$config->BOOK_VERSION";*/
	}

	public function getPublicLink() {
		if ($this->isPrivate == 1) {
			return "/books/" . $this->getPublicId();
		}

		return "/books/$this->searchName";
	}

	public function getEditLink() {
		if ($this->isPrivate == 1) {
			return "/books/edit/" . $this->getPublicId();
		}

		return "/books/edit/$this->searchName";
	}

	public function featured() {
		return ($this->labelType | BookLabelTypes::Featured) == $this->labelType;
	}

	public function mustRead() {
		return ($this->labelType | BookLabelTypes::MustRead) == $this->labelType;
	}

}
