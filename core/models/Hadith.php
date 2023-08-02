<?php

includeOnce("core/models/BaseModel.php");

class Hadith extends BaseModel
{
    public function fields()
    {
        return array_merge(parent::fields(),  array(
            "lastModifiedBy",
            "references",
            "refTags",
            "tags",
            "gradeFlag",
            "grade",
            "text",
            "notes",
            "hadith",
            "book",
            "volume",
            "collectionId",
            "databaseId"));
    }
	public function hasBooks()
	{
		return Hadith::hasBooksStatic($this->collectionId);
	}

	public static function hasBooksStatic($collectionId)
	{
		return $collectionId != 5 && $collectionId != 8;
	}

	public function hasVolumes()
	{
		return Hadith::hasVolumesStatic($this->collectionId);

	}

	public static function hasVolumesStatic($collectionId)
	{
		return $collectionId == 1 || $collectionId == 2 || $collectionId == 3 || $collectionId == 6 || $collectionId == 7 || $collectionId == 9;
	}

	public static function collectionMap()
	{
		return array(
				1 => array("short" => "bukhari", "medium" => "al-Bukhari", "long" => "Sahih al-Bukhari"),
				2 => array("short" => "muslim", "medium" => "Muslim", "long" => "Sahih Muslim"),
				3 => array("short" => "abudawood", "medium" => "Abu Dawood", "long" => "Sunan Abu Dawood"),
				4 => array("short" => "malik", "medium" => "Malik", "long" => "Maliks Muwatta"),
				5 => array("short" => "qudsi", "medium" => "Qudsi", "long" => "Hadith Qudsi"),
				6 => array("short" => "tirmidhi", "medium" => "at-Tirmidhi", "long" => "Jami` at-Tirmidhi"),
				7 => array("short" => "ibnmajah", "medium" => "Ibn Majah", "long" => "Sunan Ibn Majah"),
				8 => array("short" => "nawawi", "medium" => "al-Nawawi 40", "long" => "Nawawi 40 Hadiths"),
				9 => array("short" => "nasai", "medium" => "al-Nasaa’i", "long" => "Sunan an-Nasai"),
				10 => array("short" => "riyadussaliheen", "medium" => "Riyad-us-saliheen", "long" => "Riyaad-us-saliheen"),
				11 => array("short" => "shamail", "medium" => "Shamail at-Tirmidhi", "long" => "Shama'il at-Tirmidhi")
			);
	}

	public static function gradeFlagMap()
	{
		return array(
			1 => array("id"=> 1, "class_" => "hadith-tag-sahih", "text"=> "Sahih"),
			2 => array("id"=> 2, "class_" => "hadith-tag-hasan", "text"=> "Hasan"),
			3 => array("id"=> 4, "class_" => "hadith-tag-daeef", "text"=> "Da`eef"),
			4 => array("id"=> 8, "class_" => "hadith-tag-moudu", "text"=> "Moudu`"),
			5 => array("id"=> 16, "class_" => "hadith-tag-hasansahih", "text"=> "Hasan Sahih"),
			6 => array("id"=> 32, "class_" => "hadith-tag-munkar", "text"=> "Munkar"),
			7 => array("id"=> 64, "class_" => "hadith-tag-shadhdh", "text"=> "Shadhdh"),
			8 => array("id"=> 128, "class_" => "hadith-tag-mauquf", "text"=> "Mauquf"),
			9 => array("id"=> 256, "class_" => "hadith-tag-maqtu", "text"=> "Maqtu`"),
			10 => array("id"=> 512, "class_" => "hadith-tag-sahihinchain", "text"=> "Sahih in Chain"),
			11 => array("id"=> 1024, "class_" => "hadith-tag-daeefjiddan", "text"=> "Da`eef Jiddan"),
			12 => array("id"=> 2048, "class_" => "hadith-tag-hasaninchain", "text"=> "Hasan in Chain"),
			13 => array("id"=> 4096, "class_" => "hadith-tag-sahihlighirih", "text"=> "Sahih li-ghairih"),
			14 => array("id"=> 8192, "class_" => "hadith-tag-marfu", "text"=> "Marfu`"),
			15 => array("id"=> 16384, "class_" => "hadith-tag-mutawatir", "text"=> "Mutawatir"),
			16 => array("id"=> 32768, "class_" => "hadith-tag-mursal", "text"=> "Mursal"),
			17 => array("id"=> 65536, "class_" => "hadith-tag-lighairih", "text"=> "Hasan li-ghairih"),
			18 => array("id"=> 131072, "class_" => "hadith-tag-nochainfoundalalbani", "text"=> "No chain found (Al-Albani)"),
			19 => array("id"=> 262144, "class_" => "hadith-tag-chainisdaeefalalbani", "text"=> "Chain is da`eef (Al-Albani)"),
			20 => array("id"=> 524288, "class_" => "hadith-tag-hasangharib", "text"=> "Hasan Gharib"),
			21 => array("id"=> 1048576, "class_" => "hadith-tag-qudsi", "text"=> "Qudsi"),
			22 => array("id"=> 2097152, "class_" => "hadith-tag-sahihmouquf", "text"=> "Sahih Mouquf"),
			23 => array("id"=> 4194304, "class_" => "hadith-tag-unknown", "text"=> "No Data"),
			24 => array("id"=> 8388608, "class_" => "hadith-tag-gharib", "text"=> "Gharib"),
			25 => array("id"=> 16777216, "class_" => "hadith-tag-munqati", "text"=> "Munqati`"),
			26 => array("id"=> 33554432, "class_" => "hadith-tag-sahihbyalbani", "text"=> "Sahih (Al-Albani)")

		);
	}

	public static function buildHtmlFlags($gradeFlag) {
		$finalHTML = "";
		foreach (Hadith::gradeFlagMap() as $map) {
			if ($gradeFlag & $map["id"]) {
				$finalHTML .= "<span class='hadith-tag " . $map["class_"] . "'>" . $map["text"] . "</span>";
			}
		}
		return $finalHTML;
	}

	public static function determineCollectionIdFromShortName($shortName)
	{
		$i = 1;
		foreach (Hadith::collectionMap() as $map) {
			if ($map["short"] === $shortName) {
				return $i;
			}
			$i++;
		}
		return null;
	}

	public static function determineShortNameFromCollectionId($collectionId)
	{
		if ($collectionId < 1 || $collectionId > 11) {
			return null;
		}
		$cm = Hadith::collectionMap();
		return $cm[$collectionId]["short"];
	}

	public static function determineMediumNameFromCollectionId($collectionId)
	{
		if ($collectionId < 1 || $collectionId > 11) {
			return null;
		}
		$cm = Hadith::collectionMap();
		return $cm[$collectionId]["medium"];
	}

	public static function determineLongNameFromCollectionId($collectionId)
	{
		if ($collectionId < 1 || $collectionId > 11) {
			return null;
		}
		$cm = Hadith::collectionMap();
		return $cm[$collectionId]["long"];
	}


	public function referenceLink() {
		$refLink = "/" . Hadith::determineShortNameFromCollectionId($this->collectionId);
		if ($this->hasBooks()) {
	 		$refLink .= "/" . $this->book;
		}
		$refLink .= "/" . $this->hadith;
		return $refLink;
	}

	public function referenceLinkNumbered() {
		$refLink = $this->collectionId;
		if ($this->hasBooks()) {
	 		$refLink .= "/" . $this->book;
		}
		$refLink .= "/" . $this->hadith;
		return $refLink;
	}

	public function referenceText($typeText = "Number") {
		$cm = Hadith::collectionMap();
		$ref =  $cm[$this->collectionId]["long"] . " ";
		if ($this->hasBooks()) {
	 		$ref .= "Book $typeText " . $this->book . " ";
		}
		$ref .= "Hadith $typeText " . $this->hadith;
		return $ref;
	}

	public function reference($type = "medium") {
		$cm = Hadith::collectionMap();
		$str = $cm[$this->collectionId][$type] . "/";
		if ($this->collectionId != 5 && $this->collectionId != 8) {
			$str .= $this->book . "/";
			$str .= $this->hadith;
		} else {
			$str .= $this->hadith;
		}
		return $str;
	}

	public static function referenceFromLink($link, $type = "medium") {
		if ($link == null) {
			return null;
		}
		$parts = explode("/", $link);
		$collectionId = $parts[0];
		$cm = Hadith::collectionMap();
		$str = $cm[$collectionId][$type] . "/";
		if ($collectionId != 5 && $collectionId != 8) {
			$str .= $parts[1] . "/";
			$str .= $parts[2];
		} else {
			$str .= $parts[1];
		}
		return $str;
	}

	public function hadithLink() {
		return Hadith::referenceFromLink($this->referenceLinkNumbered(), "short");
	}
	public function hadithName() {
		return Hadith::referenceFromLink($this->referenceLinkNumbered(), "long");
	}
}

?>
