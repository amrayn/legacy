<?php 
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("pages/page.php");
$config = Config::getInstance();
// -------------------------------------------------------------------------------------
$contents = <<<PAGE
	<span style='color:#ff0000;'>Page not found! (Error 404)</span> <a href='javascript:history.go(-1)'>Go back</a>
PAGE;
// -------------------------------------------------------------------------------------
init($contents, array(
	"title" => "Page not found!"
));
?>
