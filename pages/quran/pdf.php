<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("core/utils/StringUtils.php");
includeOnce("core/utils/MediaStream.php");
AccountUtils::closeSession();
$config = Config::getInstance();

if (!isset($_GET["u"]) || $_GET["u"] == "" || !isset($_GET["s"]) || $_GET["s"] == "") {
	die("Invalid parameters");
}

$surahName = $_GET["s"];
$layout = isset($_GET["layout"]) && ($_GET["layout"] == "portrait" || $_GET["layout"] == "landscape") ? $_GET["layout"] : "portrait";
$pageNumbers = isset($_GET["pn"]) && ($_GET["pn"] == 1 || $_GET["pn"] == 0) ? $_GET["pn"] : 1;
$bin = root("/core/bin/make-pdf.sh");
$url = $config->DOMAIN . "/quran/pdfprint?" . urldecode($_GET["u"]);
if (!isset($_GET["o"]) || $_GET["o"] == "") {
	$_GET["o"] = "print";
}

$filename = $_GET["o"];
$filename .= "-" . date("iujnyHs") . rand();
$filename .= ".pdf";
$fullFilename = "/live/tmp/$filename";
$file = root($fullFilename);

$downloadFilename = "$surahName.pdf";

$output = $_SERVER['DOCUMENT_ROOT'] . $fullFilename;
$pageNumbersArg = $pageNumbers == 1 ? "true" : "false";
$command = "$bin \"$url\" \"$output\" \"$surahName\" $layout $pageNumbersArg";
exec($command);
echo "<br/>";
if (file_exists($file)) {
  $streamer = new MediaStream($file);
  $streamer->cache = false;
  $streamer->downloadFilename = $downloadFilename;
  $streamer->forceDownload = true;
  $streamer->start();
} else {
	echo "Error occured while generating your PDF. Please verify your parameters. <pre style='display:none;word-wrap: break-word;'>File: $file; Command: $command</pre>";
}

?>
