<?php
$options = array(
	"version-file:",
	"database:",
	"database-file::",
	"version::",
	"app-version::",
	"name::",
	"details::",
	"collection-id::",
	"master-db::",
	"upload",
);
$opt = getopt("", $options);
$versionFile = $opt["version-file"];
$database = $opt["database"];
$databaseFile = isset($opt["database-file"]) ? $opt["database-file"] : "db-$database.db";
$version = isset($opt["version"]) ? $opt["version"] : "increase";
$appVersion = isset($opt["app-version"]) ? $opt["app-version"] : "same";
$upload = isset($opt["upload"]);

$versionInfoFromFile = json_decode(file_get_contents($versionFile));

$found = false;
$newVersionInfo = array();
foreach ($versionInfoFromFile as $versionInfo) {
	if ($versionInfo->id == $database) {
		$versionInfo->version = $version == "increase" ? intval($versionInfo->version) + 1 : intval($version);
		$versionInfo->app = $appVersion == "same" ? $versionInfo->app : floatval($appVersion);
		$versionInfo->size = filesize($databaseFile);
		if (isset($opt["details"])) {
			$versionInfo->details = $opt["details"];
		}
		if (isset($opt["name"])) {
			$versionInfo->name = $opt["name"];
		}
		$found = true;
	}
	$newVersionInfo[] = $versionInfo;
}
if (!$found) {
	if (!isset($opt["name"]) || !isset($opt["details"])) {
		echo "\nDatabase $opt[database] not found. Please provide 'name' and 'details'\n\nExiting!";
		exit;
	}
	$appVersion = $appVersion == "same" ? 1 : intval($appVersion);
	$fileSize = filesize($databaseFile);
	$name = ($opt["name"]);
	$details = ($opt["details"]);
	$json = <<<P
		{
			"id": "$database",
			"name": "$name",
			"version": 1,
			"details": "$details",
			"url": "http://rc.amrayn.com/data/hadith.app/db-$database.db",
			"size": $fileSize,
			"app": $appVersion
		}
P;
	$obj = json_decode($json);
	if ($obj == null) {
		echo "\nPlease choose correct names without quotes\n\n";
		exit;
	} else {
		echo "\nNot found! Creating...\n\nMAKE SURE THIS DATABASE IS IN MASTER\n\n";
		if (isset($opt["collection-id"])) {
			$collectionId = $opt["collection-id"];
			$masterFile = isset($opt["master-db"]) ? $opt["master-db"] : "db-master.db";
			echo <<<P
				
sqlite3 $masterFile "INSERT INTO Collection (id, identifier, name, arabic_name, medium_name, short_name, has_books, has_volumes, total_hadiths) VALUES($collectionId, \"$database\", \"$name\", \"\", \"\", \"\", 0, 0, 0);"
sqlite3 $masterFile "INSERT INTO Language (identifier, name, collection_id, direction, font_size) VALUES(\"$database\", \"English\", $collectionId, 1, 16);"

P;
		}
	}
	$newVersionInfo[] = $obj;
}
file_put_contents($versionFile, json_encode($newVersionInfo));
$commands = array();
$commands[] = "scp $databaseFile abumq@ftp.amrayn.com:/home/amrayn/web/resources/data/hadith.app/db-$database.db";
$commands[] = "scp $versionFile abumq@ftp.amrayn.com:/home/amrayn/web/resources/data/hadith.app/version.json";

	foreach ($commands as $cmd) {
		echo($cmd . "\n");
		if ($upload) exec($cmd);
	}
