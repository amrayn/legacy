<?php
// Why this? Well, this helps enable gzip compression.
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("pages/quran/embed.php");
//header('Cache-Control: public,max-age=43200');
//header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT');
?>
