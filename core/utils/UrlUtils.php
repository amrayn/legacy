<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("core/utils/GoogleUrlShortener.php");

class UrlUtils
{
    public static function currLink($secure = false)
    {
        $uri = $_SERVER["REQUEST_URI"];
        $uri = str_replace("__json__", "", $uri);
        if (StringUtils::startsWith($uri, "//")) {
          $uri = str_replace("//", "/", $uri);
        }
        return $uri;
        //return ($secure ? "https" : "http") . "://$_SERVER[HTTP_HOST]$uri";
    }

    public static function pageRedirectParam()
    {
        return str_replace("__json__", "", $_SERVER["REQUEST_URI"]);
    }


    public static function uploadFile($url, $data)
    {
        $request = curl_init($url);

        // send a file
        curl_setopt($request, CURLOPT_POST, true);
        curl_setopt(
            $request,
            CURLOPT_POSTFIELDS,
            $data);

        // output the response
        curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($request);

        // close the session
        curl_close($request);
        return json_decode($response);
    }

    public static function downloadFile($url, $destFile, $forceDownload = true)
    {
        if (!$forceDownload && file_exists($destFile)) {
            return $destFile;
        }
        if (StringUtils::startsWith($url, "//")) {
            $url = "http:$url";
        }
        shell_exec("wget \"$url\" -O $destFile");
        // Try with file_get_contents
        if (!file_exists($destFile)) {
            file_put_contents($destFile, file_get_contents($url));
        }
        // Try with fopen
        if (!file_exists($destFile)) {
            file_put_contents($destFile, fopen($url, 'r'));
        }
        // Try with getSslPage()
        if (!file_exists($destFile)) {
            file_put_contents($destFile, UrlUtils::getSslPage($url));
        }
        if (!file_exists($destFile)) {
            Debug::log("Failed to pull URL: $url");
            return "";
        }
        return $destFile;
    }

    public static function getSslPage($url)
    {
        if (StringUtils::startsWith($url, "//")) {
            $url = "http:$url";
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_REFERER, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    public static function curlAsync($url, $params, $type = 'POST')
    {
        $post_params = array();
        foreach ($params as $key => &$val) {
            if (is_array($val)) $val = implode(',', $val);
            $post_params[] = $key . '=' . urlencode($val);
        }
        $post_string = implode('&', $post_params);

        $parts = parse_url($url);

        $fp = fsockopen($parts['host'],
            isset($parts['port']) ? $parts['port'] : 80,
            $errno, $errstr, 30);

        // Data goes in the path for a GET request
        if ('GET' == $type) $parts['path'] .= '?' . $post_string;

        $out = "$type " . $parts['path'] . " HTTP/1.1\r\n";
        $out .= "Host: " . $parts['host'] . "\r\n";
        $out .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $out .= "Content-Length: " . strlen($post_string) . "\r\n";
        $out .= "Connection: Close\r\n\r\n";
        // Data goes in the request body for a POST request
        if ('POST' == $type && isset($post_string)) $out .= $post_string;

        fwrite($fp, $out);
        fclose($fp);
    }

    public static function file_get_contents_utf8($url, $useGetSsl = false)
    {
        $context = stream_context_create(array('http' => array('header' => 'Connection: close\r\n', 'timeout' => 5)));
	if ($useGetSsl) {
		$content = UrlUtils::getSslPage($url);
	} else {
      		  $content = file_get_contents($url, false, $context);
	}
        return mb_convert_encoding($content, 'UTF-8', mb_detect_encoding($content, 'UTF-8, ISO-8859-1', true));
    }

    public static function paramValue($paramName, $default = null)
    {
        return isset($_REQUEST[$paramName]) ? $_REQUEST[$paramName] : $default;
    }

    public static function shorten($url)
    {
        $key = "AIzaSyA0_CNErhLQUlYwZzYD7G61HIL8MOvsPr8";
        $shortener = new GoogleUrlShortener($key);
        $shortUrl = $shortener->shorten($url);
        return $shortUrl === false ? $url : $shortUrl;
    }

    public static function urlToMarkup($text, $noFollow = true)
    {
        $reg_exUrl = "/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,6}(\/[a-zA-Z0-9_\-\/&\.\?=#%~\+:@,]*)?/";
        if (preg_match($reg_exUrl, $text, $url)) {

            // make the urls hyper links
            $noFollowTag = $noFollow ? "rel='nofollow'" : "";
            return preg_replace($reg_exUrl, "<a href='\$0' target='_blank' $noFollowTag>\$0</a>", $text);

        } else {

            // if no urls in the text just return the text
            return $text;

        }
    }

    public static function getHttpResponseCode($url)
    {
        $headers = get_headers($url);
        return substr($headers[0], 9, 3);
    }
}
