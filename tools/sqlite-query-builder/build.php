<?php
$longopts  = array(
    "collectionId:",
    "databaseId:",
    "database:"
);
$opt = getopt("", $longopts);

$collectionId = $opt["collectionId"];
$databaseId = $opt["databaseId"];
$database = "$opt[database]";
$filename = "$database.sql";
$dbFilename = "db-$database.db";
$sql = <<<S
SELECT concat('INSERT INTO Book (collection_id, volume, number, name, total_hadiths) VALUES(', collection_id, ',', volume, ',', book, ',\'', replace(book_name, '\'', '\'\''), '\',', 0, ');') 
FROM `HadithBookInfo` where collection_id = $collectionId AND status = 1;
SELECT concat('UPDATE Book SET total_hadiths = ', (SELECT count(*) FROM `Hadith` WHERE collection_id = HBI.collection_id AND book = HBI.book AND database_id = 1 AND status = 1), ' WHERE collection_id = ', collection_id, ' AND number = ', book, ';') 
FROM `HadithBookInfo` HBI where collection_id = $collectionId AND status = 1;
SELECT concat('INSERT INTO Hadith (language_id, collection_id, volume, book, number, text, grade, tags, ref_tags, refs, links) VALUES(', database_id, ',', collection_id, ',', volume, ',', book, ',\'', hadith, '\',\'', replace(text, '\'', '\'\''), '\',\'', COALESCE(grade_flag, 4194304), '\',\'', replace(COALESCE(`tags`, ""), '\'', '\'\''), '\',\'', replace(COALESCE(`ref_tags`, ""), '\'', '\'\''), '\',\'', replace(COALESCE(`references`, ""), '\'', '\'\''), '\',\'', replace(COALESCE(`links`, ""), '\'', '\'\''), '\');') 
FROM `Hadith` where collection_id = $collectionId AND status = 1 and database_id = $databaseId order by database_id, volume, book, hadith;
S;

file_put_contents("output/$collectionId.sql", $sql);
exec("mysql amrayndb < output/$collectionId.sql > output/$filename");
exec("rm output/$collectionId.sql");
exec("sed -i '/^concat/ d' output/$filename");
exec("sed -i 's/\\\\n/\\n/g' output/$filename");
exec("rm error_log");
$sql = <<<S
DROP TABLE IF EXISTS "Book";
DROP TABLE IF EXISTS "Hadith";
CREATE TABLE "Book" (
	`id`	INTEGER PRIMARY KEY AUTOINCREMENT,
	`collection_id`	INTEGER NOT NULL,
	`volume`	INTEGER,
	`number`	INTEGER NOT NULL,
	`name`	TEXT NOT NULL,
	`arabic_name`	TEXT,
	`total_hadiths`	INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE "Hadith" (
	`id`	INTEGER PRIMARY KEY AUTOINCREMENT,
	`language_id`	INTEGER NOT NULL,
	`collection_id`	INTEGER NOT NULL,
	`volume`	INTEGER,
	`book`	INTEGER,
	`number`	VARCHAR(10) NOT NULL,
	`text`	TEXT NOT NULL,
	`grade`	INTEGER NOT NULL,
	`tags`	VARCHAR(255),
	`ref_tags`	VARCHAR(255),
	`refs`	VARCHAR(255),
	`links`	TEXT
);
CREATE INDEX `book_basic_idx` ON `Book` (`id` ,`collection_id` ,`volume` ,`number` );
CREATE UNIQUE INDEX `book_uniq_idx` ON `Book` (`collection_id` ,`volume` ,`number` );
CREATE INDEX `hadith_basic_idx` ON `Hadith` (`grade` ,`tags` ,`ref_tags` );
CREATE UNIQUE INDEX `hadith_uniq_idx` ON `Hadith` (`language_id` ,`collection_id` ,`volume` ,`book` ,`number` );
S;
file_put_contents("output/$database.full.sql", $sql);
file_put_contents("output/$database.full.sql", file_get_contents("output/$filename"), FILE_APPEND);
exec("mv output/$database.full.sql output/$filename");
file_put_contents("output/$database.sh", "\nsqlite3 $dbFilename < $filename");

// master update
$sql = <<<S
SELECT concat('UPDATE Collection SET total_hadiths = ', (SELECT count(*) FROM `Hadith` WHERE collection_id = HBI.collection_id AND database_id = 1 AND status = 1), ' WHERE collection_id = ', collection_id, ';') 
FROM `HadithBookInfo` HBI where collection_id = $collectionId AND status = 1 LIMIT 1;
S;
file_put_contents("output/$collectionId.sql", $sql);
exec("mysql amrayndb < output/$collectionId.sql > output/master-$database.sql");
exec("rm output/$collectionId.sql");
exec("sed -i '/^concat/ d' output/master-$database.sql");
file_put_contents("output/$database.sh", "\nsqlite3 db-master.db \"" . trim(file_get_contents("output/master-$database.sql")) . "\"", FILE_APPEND);
exec("rm output/master-$database.sql");
