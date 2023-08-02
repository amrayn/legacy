<?php

include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("core/utils/StringUtils.php");
includeOnce("core/models/SurahInfo.php");
includeOnce("core/utils/DateUtils.php");
//includeOnce("core/utils/ISBN.php");

class Utils
{
    public static function requireAuth($user, $pwd)
    {
        $AUTH_USER = $user;
        $AUTH_PASS = $pwd;
        header('Cache-Control: no-cache, must-revalidate, max-age=0');
        $has_supplied_credentials = !(empty($_SERVER['PHP_AUTH_USER']) && empty($_SERVER['PHP_AUTH_PW']));
        $is_not_authenticated = (
            !$has_supplied_credentials ||
            $_SERVER['PHP_AUTH_USER'] != $AUTH_USER ||
            $_SERVER['PHP_AUTH_PW']   != $AUTH_PASS
        );
        if ($is_not_authenticated) {
            header('HTTP/1.1 401 Authorization Required');
            header('WWW-Authenticate: Basic realm="Access denied"');
            return false;
        }
        return true;
    }

    public static function createFullImage($imgUrl)
    {
        $fullyResolvedThumbnail = $imgUrl;
        if (strlen($imgUrl) > 18) {
            if (substr($imgUrl, 0, 18) === "//amrayn.com/assets/images/") {
                $fullyResolvedThumbnail = "https:$imgUrl";
            } elseif (strlen($imgUrl) > 23 && substr($imgUrl, 0, 23) === "http://amrayn.com/assets/images/") {
                $fullyResolvedThumbnail = "https://amrayn.com/assets/images/" . substr($imgUrl, 23);
            }
        }
        return $fullyResolvedThumbnail;
    }

    public static function sortArrayByArray(array $array, array $orderArray)
    {
        $ordered = array();
        foreach ($orderArray as $key) {
            if (array_key_exists($key, $array)) {
                $ordered[$key] = $array[$key];
                unset($array[$key]);
            }
        }
        return $ordered + $array;
    }

    public static function encodeError($msg)
    {
        return json_encode(array("error" => $msg));
    }

    public static function buildQuranRef($referenceString, $surahInfoWithVerseInfo)
    {
        $ref = explode(":", $referenceString);
        $surah = $ref[0];
        $maxVerses = SurahInfo::getInfoByTypeAndSurah(SurahInfoTypes::VerseCount, $surah, $surahInfoWithVerseInfo, true);
        if (count($ref) > 1) {
            // We have verses
            $verses = explode("-", $ref[1]);
            $verseStart = $verses[0];
            if ($verseStart < 1) {
                $verseStart = 1;
            }
            if (count($verses) > 1) {
                $verseEnd = $verses[1];
                if ($verseEnd < $verseStart) {
                    $verseEnd = $verseStart;
                } elseif ($verseEnd > $maxVerses) {
                    $verseEnd = $maxVerses;
                }
            } elseif (count($verses) == 1) {
                $verseEnd = $verseStart;
            } else {
                $verseEnd = $maxVerses;
            }
        } else {
            $verseStart = 1;
            $verseEnd = $maxVerses;
        }
        return array("surah" => $surah, "start" => $verseStart, "end" => $verseEnd);
    }
    public static function translateFavVal($favVal)
    {
        $favVal = urldecode($favVal);
        $valueId = StringUtils::normalizePublicId($favVal);
        $valueId = StringUtils::decryptText($valueId);
        if ($valueId !== false && strlen($valueId) > 0) {
            $VALUE_PUBLIC_ID_REPLACE_MAP_KEYS = array(
                "Audio-", "AudioSeries-", "Hadith-", "Book-", "BookVolume-"
            );
            $VALUE_PUBLIC_ID_REPLACE_MAP_VALUES = array(
                "le", "se", "", "bk", "bv"
            );
            $favVal = str_replace($VALUE_PUBLIC_ID_REPLACE_MAP_KEYS, $VALUE_PUBLIC_ID_REPLACE_MAP_VALUES, $valueId);
        }
        return $favVal;
    }
    public static function translateBookmarkKey($publicId)
    {
        $publicId = urldecode($publicId);
        $sourceId = StringUtils::normalizePublicId($publicId);
        $sourceId = StringUtils::decryptText($sourceId);
        if ($sourceId !== false && strlen($sourceId) > 0) {
            $VALUE_PUBLIC_ID_REPLACE_MAP_KEYS = array(
                "Audio-", "AudioSeries-", "Hadith-", "Book-", "BookVolume-"
            );
            $VALUE_PUBLIC_ID_REPLACE_MAP_VALUES = array(
                "", "", "", "", ""
            );
            return intval(str_replace($VALUE_PUBLIC_ID_REPLACE_MAP_KEYS, $VALUE_PUBLIC_ID_REPLACE_MAP_VALUES, $sourceId));
        }
        return -1;
    }

    public static function bytesToUnit($bytes, $decimals = 2)
    {
        if ($bytes == -1) {
            return "ERR";
        }
        $sz = 'BKMGTP';
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
    }

    public static function timeToMarkup($text)
    {
        $reg_exUrl = "/([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})/";
        if (preg_match($reg_exUrl, $text, $url)) {
            return preg_replace($reg_exUrl, "<span class='ln-time' sec='" . DateUtils::timeToSec($url[0]) . "'>\$0</span>", $text);
        } else {
            return $text;
        }
    }


    public static function findBookmarkBySourceId($bookmarks, $sourceId)
    {
        foreach ($bookmarks as $bookmark) {
            if ($bookmark->sourceId == $sourceId) {
                return $bookmark;
            }
        }
        return null;
    }

    public static function determineLanguageById($languageId)
    {
        switch ($languageId) {
            case 1:
                return "English";
            case 2:
                return "Urdu";
            case 3:
                return "Arabic";
            case 4:
                return "Pashto";
            default:
                return "Unknown";
        }
    }
}
