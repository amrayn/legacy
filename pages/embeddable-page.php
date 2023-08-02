<?php
include_once($_SERVER["DOCUMENT_ROOT"] . "/_.php");

includeOnce("core/utils/StringUtils.php");
includeOnce("core/utils/ObjectUtils.php");
includeOnce("core/utils/UrlUtils.php");

includeOnce("core/Context.php");
function getJSResources($list, $nocompress = false)
{
	$config = Config::getInstance();
	$resources = "";
	foreach ($list as &$item) {
		$resources .= "<script src='$item?mv=5&v=$config->VERSION'></script>\n";
	}
	return $resources;
}
function getCSSResources($list, $nocompress = false)
{
	$config = Config::getInstance();
	$resources = "";
	foreach ($list as &$item) {
		$resources .= "<link rel='stylesheet' type='text/css' href='$item?mv=5&v=$config->VERSION' />\n";
	}
	return $resources;
}
function init($contents, $customOptions = array())
{
	$config = Config::getInstance();
	$defaultOptions = array(
			"title" => "Page",
			"context" => Context::Quran,
			"meta_name" => "amrayn",
			"meta_title" => "",
			"meta_description" => "Various authentic Islamic resources including Quran translations (incl. word by word meanings), tafseers, hadith, lectures, recitations and advanced search.",
			"meta_keywords" => "",
			"other_metadata" => array(),
			"extra_head_html" => "",
			"breadcrumbs" => array()
	);
	$options = array_merge($defaultOptions, $customOptions);
	Context::setCurrent($options["context"]);
	$options["title"] = str_replace('\'', '', $options["title"]);
	$options["meta_keywords"] = str_replace('\'', '', $options["meta_keywords"]);
	$options["meta_name"] = str_replace('\'', '', $options["meta_name"]);
	$options["meta_description"] = strip_tags(str_replace('\'', '&#39;', $options["meta_description"]));
	if (trim($options["meta_title"]) === "") {
		$options["meta_title"] = $options["title"];
	}
	if (trim($options["meta_description"]) === "") {
		$options["meta_description"] = $defaultOptions["meta_description"];
	}
	$options["title"] .= " - amrayn";
	$opt = ObjectUtils::buildFromArray($options, true);
	if (isset($_GET["ignore-pageview"])) {
		$contents .= "<!--Ignoring pageview-->";
	} else {
		$contents .= "<script>onloads[onloads.length++] = Analytics.pageView;</script>";
	}
	echo head($opt);
	echo body($opt);
	echo $contents;
	echo foot($opt);
}

function head($opt)
{
	$config = Config::getInstance();
	$currentContextJson = json_decode(Context::getCurrent());
	$currentContextId = $currentContextJson->id;
	$headHTML = <<<HEAD
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html class="transitional-page" xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<head>
	<meta content='text/html;charset=utf-8' http-equiv='Content-Type' />
	<meta content='utf-8' http-equiv='encoding' />
	<meta name='google' value='notranslate' />
	<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0,user-scalable=0' />
HEAD;
	$headHTML .= $opt->extra_head_html;
	$analyticsEnabled = $config->ANALYTICS_ENABLED ? "true" : "false";
	$debugEnabled = isset($_COOKIE["debug"]) ? "true" : "false";
	$jsResources = getJSResources(array(
		"/scripts/nprogress.js",
		"/scripts/sequential-animations.js",
		"/scripts/Debug.js",
		"/scripts/Analytics.js",
		"/scripts/utils.js",
		"/scripts/share.js",
		"/scripts/tooltip.js",
		"/scripts/notyfy.js",
		"/scripts/alertify.js",
		"/scripts/favico.js",
		"/scripts/IslamicCalendar.js",
		"/scripts/PrayTimes.js",
		"/scripts/User.js"
	));
	$jsResourcesNonCompressed = getJSResources(array(
		"/scripts/jquery-2.1.0.min.js",
		"/scripts/jquery-ui-1.10.4.min.js",
		"/scripts/jquery-cookie-1.4.1.min.js",
		"/scripts/underscore.min.js",
		"/scripts/html2canvas.min.js"
	), true);
	$themeValueData = AccountUtils::accountData(AccountDataKeys::Theme);
	$themeValue = $themeValueData === null ? "default" : $themeValueData->value;
	$cssResources = getCSSResources(array(
		"/styles/nprogress.css",
		"/styles/layout.css",
		"/styles/quran.css",
		"/styles/tooltip.css",
		"/styles/notyfy.css",
		"/styles/notyfy.theme.default.css",
		"/styles/alertify.css",
		"/styles/alertify.theme.default.css",
		"/styles/themes/default.css" // we always use default for embedded pages
	));
	$embeddedJS = "var currentContextJson = " . json_encode($currentContextJson) . ";";
	$svcArgsNoVersion = array();
	$svcArgs = array("__v=$config->VERSION");
	if (AccountUtils::isLoggedIn()) {
		$user = AccountUtils::currentUser();
		$svcArgs[] = "li=" . ($user->id + 261);
		$svcArgsNoVersion[] = "li=" . ($user->id + 261);
		if ($user->hasPreference(UserPreference::ProtectionMode)) {
			$svcArgs[] = "pr";
			$svcArgsNoVersion[] = "pr";
		}
	}
	$svcArgs = implode("&", $svcArgs) . "&";
	$svcArgsNoVersion = implode("&", $svcArgsNoVersion) . "&";
	$embeddedJS .= <<<JS
				var isLoggedIn = false;
				var __domain = '$config->DOMAIN_VAGUE';
				var __domain_secure = '$config->DOMAIN_SECURE';
				var STATIC_RESOURCES_BASE = '$config->STATIC_RESOURCES_BASE';
				var STATIC_IMAGES_BASE = '$config->STATIC_IMAGES_BASE';
				var __version = $config->VERSION;
				var __img_version = $config->IMG_VERSION;
				var __svcargs = "?$svcArgs";
				var __svcargs_mid = "&$svcArgs";
				var __svcargs_no_ver = "?$svcArgsNoVersion";
				var __svcargs_no_ver_mid = "&$svcArgsNoVersion";
				IntializePageOnce();
				var onloads = [];
				Analytics.enabled = $analyticsEnabled;
				Debug.enabled = $debugEnabled;
				$(document).ready(function() {
					for (var i = 0; i < onloads.length; ++i) {
						onloads[i]();
					}
					$(document).data("pageLoadRequest", null);
				});
JS;
	if ($config->ANALYTICS_ENABLED) {
		$embeddedJS .= <<<JS
				(function (i, s, o, g, r, a, m) {
					  i['GoogleAnalyticsObject'] = r;
					  i[r] = i[r] || function () {
						  (i[r].q = i[r].q || []).push(arguments)
					  }, i[r].l = 1 * new Date();
					  a = s.createElement(o),
					  m = s.getElementsByTagName(o)[0];
					  a.async = 1;
					  a.src = g;
					  m.parentNode.insertBefore(a, m)
				  })(window, document, 'script', '//www.google-analytics.com/analytics.js', 'ga');
				if (Analytics.enabled) {
					ga('create', 'UA-55401043-5', 'auto');
					ga('require', 'linkid', 'linkid.js');
					ga('require', 'displayfeatures');
				}
JS;
	}
	$headHTML .= <<<HEAD
	        <link rel='shortcut icon' href='/favicon.png' type='image/png' />
			<link rel='apple-itouch-icon' href='/favicon.png' >
			<link rel='stylesheet' type='text/css' href='/styles/font/font-awesome-4.5.0/css/font-awesome.min.css' />
			<link rel='stylesheet' type='text/css' href='/styles/font/et-line-font/style.css?v2' />
			<link rel='stylesheet' type='text/css' href='/styles/jquery-ui-1.11.1.custom/jquery-ui.min.css' />
			$cssResources
			$jsResourcesNonCompressed
			$jsResources
			<script>
				$embeddedJS
			</script>
			<title>$opt->title</title>
			<style>
			html {
				padding:0px !important;
			}
			body {
				background:none;
			}
			#body-contents {
				padding:0px !important;
				margin:0px !important;
				line-height:0px !important;
			}
			</style>
	</head>
HEAD;
	return $headHTML;
}

function body($opt)
{
	return "<body><div id='body-contents'>";
}

function foot($opt)
{
	$config = Config::getInstance();
  $poweredByLink = empty($opt) || empty($opt->powered_by_link) ? $config->DOMAIN : $opt->powered_by_link;
	return "</div><div class='embedded-foot'>Powered by <a href='$poweredByLink' target='_parent'><img src='https://amrayn.com/assets/images/logo-embed.png?4' style='width: 74px;position: relative;top: 7px;vertical-align:super;'/></a></div></body></html>";
}


?>
