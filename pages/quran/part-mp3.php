<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("core/utils/StringUtils.php");
includeOnce("core/utils/MediaStream.php");
includeOnce("core/queries/VerseByVerseRecitersQueries.php");
AccountUtils::closeSession();
$config = Config::getInstance();

if (!isset($_GET["s"]) || $_GET["s"] == "" || !isset($_GET["v"]) || $_GET["v"] == "" || !isset($_GET["r"]) || $_GET["r"] == "") {
	die("Invalid parameters");
}

$surah = $_GET["s"];
$verseRange = $_GET["v"];
$reciter = $_GET["r"];
$includeBismillah = isset($_GET["bism"]);
$bin = root("/core/bin/mp3wrap/hacked-mp3wrap/mp3wrap"); // hacked version of mp3wrap by @abumq to handle custom stuffs for Quran and meta/exif

$reciterData = VerseByVerseRecitersQueries::queryById($reciter);

// NOTE: directoryName is no longer available
//       we have patternUrl
$filename = $surah . "_" . $verseRange . "_" . $reciterData->directoryName . ($includeBismillah ? "-with-bism" : "") . ".mp3";

$fullFilename = "/live/tmp/$filename";
$file = root($fullFilename);

$output = $_SERVER['DOCUMENT_ROOT'] . $fullFilename;
$filesList = array();
$downloadFilename = $filename;
$s = str_pad($surah, 3, "0", STR_PAD_LEFT);
$fullPath = "/home/amusa/resources/verse-by-verse/$reciterData->directoryName/";
$verses = explode("-", $verseRange);
if (count($verses) == 0) {
	die("Invalid range.");
}
$firstAyah = intval($verses[0]);
$lastAyah = intval($verses[count($verses) - 1]);
if ($firstAyah > $lastAyah) {
	die("Invalid range");
}
if (($lastAyah - $firstAyah) > 50) {
	die("Invalid range, please select maximum 50 ayahs");
}
if ($firstAyah === 1 && $includeBismillah) {
	$filesList[] = $fullPath . "bismillah.mp3";
}
$fullPath .= "$s/";

for ($i = $firstAyah; $i <= $lastAyah; ++$i) {
				$v = str_pad($i, 3, "0", STR_PAD_LEFT);
	$currFilename = $fullPath.$s.$v.".mp3";
	if (file_exists($currFilename)) {
		$filesList[] = $currFilename;
	} else {
		die("File for verse $i in surah $surah does not exist for reciter $reciter, please report it on https://github.com/amrayn/amrayn.com");
	}
}
if (count($filesList) === 0) {
	die("Failed to get the files list");
}
$filesStr = implode(" ", $filesList);
$title = $surah . "_" . $verseRange;
$simpleCopy = false;
if ($firstAyah == $lastAyah && !$includeBismillah) {
	$command = "cp " . $filesList[0] . " $output";
	$simpleCopy = true;
} else {
	$command = "$bin $title $output $filesStr";
}
if (Debug::on()) { echo $command; exit; }
if (!file_exists($file)) {
	exec($command);
}
if (!$simpleCopy) {
	$fileNew = substr($file, 0, strpos($file, ".mp3"));
	$file = $fileNew . "_MP3WRAP.mp3";
}
echo "<br/>";
if (file_exists($file)) {
	chmod($file, 0644);
        $streamer = new MediaStream($file);
        $streamer->cache = false;
        $streamer->downloadFilename = $downloadFilename;
        $streamer->forceDownload = true;
        $streamer->start();
} else {
	echo "Error occured while generating your mp3. Please verify your parameters.";
	//echo "<pre style='display:none;word-wrap: break-word;'>File: $file; Command: $command</pre>";
}

?>
