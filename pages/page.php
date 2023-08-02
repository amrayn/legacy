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
		$resources .= "<script src='$item?v=$config->VERSION&1093'></script>\n";
	}
	return $resources;
}
function getCSSResources($list, $nocompress = false)
{
	$config = Config::getInstance();
	$resources = "";
	foreach ($list as &$item) {
		$resources .= "<link rel='stylesheet' type='text/css' href='$item?v=$config->VERSION+6' />\n";
	}
	return $resources;
}
function init($contents, $customOptions = array())
{
	$config = Config::getInstance();
	$defaultOptions = array(
			"title" => "Page",
			"context" => Context::Quran,
			"meta_name" => "Amrayn",
			"meta_title" => "",
			"meta_description" => "Various authentic Islamic resources including Quran translations (incl. word by word meanings), tafseers, hadith, lectures, recitations and advanced search.",
			"meta_keywords" => "",
			"meta_images" => array("https://cdn.amrayn.com/assets/images/logo.png"),
			"other_metadata" => array(),
			"extra_head_html" => "",
			"breadcrumbs" => array(),
			"is_amp" => false
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
	$options["title"] .= " - Amrayn";
	$opt = ObjectUtils::buildFromArray($options, true);
	if (isset($_GET["ignore-pageview"])) {
		$contents .= "<!--Ignoring pageview-->";
	} else {
		$contents .= "<script>onloads[onloads.length++] = Analytics.pageView;</script>";
	}
	if (isset($_GET["__json__"])) {
		echo json_encode(array(
			"contents" => trim($contents),
			"options" => $opt
		));
	} else {
		echo head($opt);
        echo body($opt);
		echo $contents;
		echo foot();
	}
}

function head($opt)
{
	$config = Config::getInstance();
	$currentContextJson = json_decode(Context::getCurrent());
	$currentContextId = $currentContextJson->id;
        $ampHtml = $opt->is_amp ? "amp" : "";
        $ampJs = $opt->is_amp ? "<script async src='https://cdn.ampproject.org/v0.js'></script>" : "";
        $ampStyle = $opt->is_amp ? "<style amp-boilerplate>body{-webkit-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-moz-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-ms-animation:-amp-start 8s steps(1,end) 0s 1 normal both;animation:-amp-start 8s steps(1,end) 0s 1 normal both}@-webkit-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-moz-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-ms-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-o-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}</style><noscript><style amp-boilerplate>body{-webkit-animation:none;-moz-animation:none;-ms-animation:none;animation:none}</style></noscript>" : "";
        $selfUrl = UrlUtils::currLink(true);
	$headHTML = <<<HEAD
<!DOCTYPE html>
<html $ampHtml>
	<head>
                <link rel="canonical" href="$selfUrl" />
                <meta charset="utf-8">
		<meta content='text/html;charset=utf-8' http-equiv='Content-Type' />
		<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0,minimum-scale=1.0,user-scalable=0' />
                $ampStyle
                $ampJs
		<meta name='google' value='notranslate' />
		<meta id='name-meta' itemprop='name' content='$opt->meta_name' />
		<meta id='title-meta' itemprop='title' content='$opt->meta_title' />
		<meta property='og:title' content='$opt->meta_title' />
		<meta property='og:site_name' content='Amrayn' />
		<meta id='keywords-meta' http-equiv='keywords' itemprop='keywords' name='keywords' content='$opt->meta_keywords' />
		<meta id='description-meta' http-equiv='description' name='description' content='$opt->meta_description' />
		<meta property='og:description' content='$opt->meta_description' />
		<script type="text/javascript">
			(function(f,b){if(!b.__SV){var e,g,i,h;window.mixpanel=b;b._i=[];b.init=function(e,f,c){function g(a,d){var b=d.split(".");2==b.length&&(a=a[b[0]],d=b[1]);a[d]=function(){a.push([d].concat(Array.prototype.slice.call(arguments,0)))}}var a=b;"undefined"!==typeof c?a=b[c]=[]:c="mixpanel";a.people=a.people||[];a.toString=function(a){var d="mixpanel";"mixpanel"!==c&&(d+="."+c);a||(d+=" (stub)");return d};a.people.toString=function(){return a.toString(1)+".people (stub)"};i="disable time_event track track_pageview track_links track_forms track_with_groups add_group set_group remove_group register register_once alias unregister identify name_tag set_config reset opt_in_tracking opt_out_tracking has_opted_in_tracking has_opted_out_tracking clear_opt_in_out_tracking start_batch_senders people.set people.set_once people.unset people.increment people.append people.union people.track_charge people.clear_charges people.delete_user people.remove".split(" ");
			for(h=0;h<i.length;h++)g(a,i[h]);var j="set set_once union unset remove delete".split(" ");a.get_group=function(){function b(c){d[c]=function(){call2_args=arguments;call2=[c].concat(Array.prototype.slice.call(call2_args,0));a.push([e,call2])}}for(var d={},e=["get_group"].concat(Array.prototype.slice.call(arguments,0)),c=0;c<j.length;c++)b(j[c]);return d};b._i.push([e,f,c])};b.__SV=1.2;e=f.createElement("script");e.type="text/javascript";e.async=!0;e.src="undefined"!==typeof MIXPANEL_CUSTOM_LIB_URL?
			MIXPANEL_CUSTOM_LIB_URL:"file:"===f.location.protocol&&"//cdn.mxpnl.com/libs/mixpanel-2-latest.min.js".match(/^\/\//)?"https://cdn.mxpnl.com/libs/mixpanel-2-latest.min.js":"//cdn.mxpnl.com/libs/mixpanel-2-latest.min.js";g=f.getElementsByTagName("script")[0];g.parentNode.insertBefore(e,g)}})(document,window.mixpanel||[]);
			
			mixpanel.init('b6d44ee54feb5ecb07829d5d20659ef7'); 
		</script>
		<meta name="twitter:title" content="$opt->meta_title">
		<meta name="twitter:description" content="$opt->meta_description">
HEAD;
	foreach ($opt->other_metadata as $meta) {
			$headHTML .= "
	<meta ";
			foreach ($meta as $key => $value) {
				$headHTML .= "$key='$value' ";
			}
			$headHTML .= "/>
			";
	}
	$hasCustomImage = isset($_GET["cimg"]);
	if ($hasCustomImage) {
		$decryptCustomImageUrl = StringUtils::advancedDecryptText($_GET["cimg"]);
		if ($decryptCustomImageUrl !== false) {
			array_unshift($images, $decryptCustomImageUrl);
		}
	}
	foreach ($opt->meta_images as $metaImg) {
        $url = StringUtils::startsWith($metaImg, "//") ? "http:$metaImg" : $metaImg;
		$headHTML .= "
			<meta property='og:image' content='$url'/>
			<link rel='image_src' type='image/png' href='$url?v=$config->IMG_VERSION' />
		";
	}
	$headHTML .= $opt->extra_head_html;
	$isLoggedIn = "true";
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
		"/scripts/html2canvas.min.js",
		"/scripts/desktop-notifications.js"
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
		"/styles/themes/$themeValue.css"
	));
	$embeddedJS = "var currentContextJson = " . json_encode($currentContextJson) . ";";
	$svcArgsNoVersion = array();
	$usid = "0";
	$svcArgs = array("__v=$config->VERSION+5", "_v2=2c");
	$svcArgs = implode("&", $svcArgs) . "&";
	$svcArgsNoVersion = implode("&", $svcArgsNoVersion) . "&";
	$timeInfo = isset($_SESSION["timeInfo"]) ? json_encode($_SESSION["timeInfo"]) : "{}";
	$userPrayerTimeMethod = AccountUtils::accountData(AccountDataKeys::PrayerTimeMethod);
	$userPrayerTimeMethod = $userPrayerTimeMethod === null ? "Makkah" : $userPrayerTimeMethod->value;
	$userPrayerTimeAdjustment = AccountUtils::accountData(AccountDataKeys::PrayerTimeAdjustment);
	$userPrayerTimeAdjustment = $userPrayerTimeAdjustment === null ? "0" : $userPrayerTimeAdjustment->value;
        if(!empty($_SERVER['HTTP_CLIENT_IP'])){
            $ip=$_SERVER['HTTP_CLIENT_IP'];
        } elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
            $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
        } else{
            $ip=$_SERVER['REMOTE_ADDR'];
        }
	$myIp = StringUtils::advancedEncryptText("1276_$ip");
	$embeddedJS .= <<<JS
				var __enip = '$myIp';
				var __domain = '$config->DOMAIN_VAGUE';
				var __domain_secure = '$config->DOMAIN_SECURE';
				var STATIC_RESOURCES_BASE = '$config->STATIC_RESOURCES_BASE';
				var STATIC_IMAGES_BASE = '$config->STATIC_IMAGES_BASE';
				var __version = $config->VERSION;
				var __img_version = $config->IMG_VERSION;
				var __svcargs = "?$svcArgs";
				var __svcargs_mid = "&$svcArgs";
				var __svcargs_no_ver = "?$svcArgsNoVersion";
				var __usid = "$usid";
				var __svcargs_no_ver_mid = "&$svcArgsNoVersion";
				var isLoggedIn = $isLoggedIn;
				IntializePageOnce();
				var onloads = [];
				Utils.housekeeping(Math.random());
				Analytics.enabled = $analyticsEnabled;
				Debug.enabled = $debugEnabled;
				//Utils.warnSelfXss();
				var userTimeInfo = $timeInfo;
				var userPrayerTimeMethod = "$userPrayerTimeMethod";
				var userPrayerTimeAdjustment = parseInt("$userPrayerTimeAdjustment");
				var prayTimes = new PrayTimes(userPrayerTimeMethod);
				var userPrayerTimes = getUserPrayerTimes();
                NProgress.settings.minimum = 0.4;
JS;
	$forceTimezoneUpdate = DateUtils::getTimezoneName() == "" || DateUtils::getTimezoneName() == "UTC" || !isset($_COOKIE["nz"]);
	if (!$forceTimezoneUpdate) {
		// We force it when it's FORCE_TIMEZONE_UPDATE_THRESHOLD_SECONDS config value
		$diff = DateUtils::datediff(DateUtils::getLastTimezoneUpdate(), DateUtils::newDateUtc());
		$forceTimezoneUpdate = isset($_GET["force-tzu"]) || isset($_GET["ftzu"]) || $diff > $config->FORCE_TIMEZONE_UPDATE_THRESHOLD_SECONDS;
		if ($forceTimezoneUpdate) {
			$embeddedJS .= "// Forcing tzu (Diff: $diff)\n";
		} else {
			$embeddedJS .= "// Force tzu in " . ($config->FORCE_TIMEZONE_UPDATE_THRESHOLD_SECONDS - $diff) . "s\n";
		}
	}
	if ($forceTimezoneUpdate) {
		$embeddedJS .= <<<JS
					var sendTzu = function(parameters) {

					}
					var tzuRequest = function(updateLatLng, offset) {
						var ud = new Date();
						var tzoff = (-ud.getTimezoneOffset() / 60);
						var udtz = offset == undefined || (typeof offset === "function") ? tzoff : offset;
						var parameters = "off=" + udtz + "&dst=" + (ud.dst() ? "1" : "0");
						if (updateLatLng && navigator.geolocation) {
							navigator.geolocation.getCurrentPosition(function(pos) {
								parameters += "&lat=" + pos.coords.latitude + "&lng=" + pos.coords.longitude;
								sendTzu(parameters);
							}, function (error) {
								if (error.code == error.PERMISSION_DENIED) {
									removeCookie("accl");
								}
								sendTzu(parameters);
							});
						} else {
							sendTzu(parameters);
						}
					};
JS;
	} else {
		$embeddedJS .= "var tzuRequest = function (offset) {var d='" . DateUtils::currentLocalDateStr() . "';};";
	}
	$user = AccountUtils::currentUser();
	if ($user !== null) {
		$prayerReminder = $user->hasPreference(UserPreference::PrayerReminder) ? "true" : "false";
		$embeddedJS .= "User.name = '$user->name';";
		$embeddedJS .= "User.Preferences.manualUpdateNofication = " . ($user->hasPreference(UserPreference::ManualUpdateNofication) ? "true" : "false") . ";";
		$embeddedJS .= "User.isSuperUser = " . ($user->isSuperUser() ? "true" : "false") . ";";
		$embeddedJS .= <<<JS
			function desktopPermissionRequired(detailed) {
				var message = "We need desktop notification permissions in order to remind you to pray.";
				if (detailed) {
					message += "<br><br>Either enable it from your browser settings or uncheck 'Prayer Reminder' from settings.";
				}
				Utils.notifyUser({
					text: message,
					type: "warning",
					timeout: 15000
				});
			}
			onloads[onloads.length] = function() {

				if ($prayerReminder && (Notification.permission === 'unknown' || Notification.permission === 'default')) {
					Notification.requestPermission(function(permission) {
						if (permission === 'denied') {
							desktopPermissionRequired(false);
						}
					});
				} else if ($prayerReminder && Notification.permission === 'denied') {
					Notification.requestPermission(function(permission) {
						if (permission === 'denied') {
							desktopPermissionRequired(true);
						}
					});
				}
			};
			setInterval(function() {
			    if ($(".prayernext").length == 0) {
			        return;
			    }
				var oldStatus = $(".prayernext").attr("class").split(" ")[1];
				var prayerName = $("tr.prayernext > td:nth-child(1)").text();
				updateUserPrayerMenu();
				var newStatus = $(".prayernext").attr("class").split(" ")[1];

				if (oldStatus != newStatus) {
					if (oldStatus == "critical" && newStatus == "easy") {
						Utils.notifyUser({
							text: "Time for prayer",
							type: "success",
							timeout: 15000
						});
						if ($prayerReminder) {

						}
					}
				}
			}, 60 * 1000);
			if (!User.Preferences.manualUpdateNofication) {

				// see User.js:308
				setInterval(function() {
					User.Notification.refresh();
				}, $config->NOTIFICATION_REFRESH_DELAY_SEC * 1000);
			}
JS;
	}
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
	$embeddedJS .= <<<JS
				var generateMessageBar = function() {
					var cookieName = "nomsgbar_env";
					var showMessageBarOnLargeDevice = false;
					var message = "";
					//message= "Try our new <a href='/livesearch/quran?q=Allah' style='color:#fff' rel='nofollow'>live search</a>";
					//showMessageBarOnLargeDevice = true;
					var overrideColor = "";
					//overrideColor = "rgb(185, 0, 24) !important";
					//overrideColor = "rgb(200, 0, 0) !important";
					if (isLoggedIn || $("#message-bar").length > 0) {
						return;
					}
					var lastMessagebarClosed = cook(cookieName, null);
					if (lastMessagebarClosed !== null) {
						lastMessagebarClosed = new Date(lastMessagebarClosed);
						// if closed within x days then ignore
						if ((new Date()) - lastMessagebarClosed < (15 * 86400 * 1000)) {
							return;
						}
					}
					var height = 30;
					var relativeHeightPx = (height + 1) + "px";
					$("#body-head-large").css("top", relativeHeightPx);
					$("#body-head-medium").css("top", relativeHeightPx);
					$("#body-head-small").css("top", relativeHeightPx);
					$("#header-bar").css("top", relativeHeightPx);
					var messageBar = $("<div></div>").attr("id", "message-bar").css({
						"height" : height,
						"background-color" : overrideColor
					});
					if (!showMessageBarOnLargeDevice) {
						messageBar.addClass("hide-on-large-devices");
					}
					$("#body-head").prepend(messageBar);
					$("#header-bar").prepend(messageBar);
					messageBar.html("<!--googleoff: index--><span id='message-bar-close' class='fa fa-close'></span><span id='message-bar-text'>" + message + "</span><!--googleon: index-->");
					messageBar.append("<div style='clear:both'></div>");
					$("#message-bar-close").click(function() {
						saveCookie(cookieName, new Date());
						$("#message-bar").remove();
						Analytics.userAction("Message Bar Closed");
					});
				}
				$(document).ready(function() {
					for (var i = 0; i < onloads.length; ++i) {
						onloads[i]();
					}
					tzuRequest(cook("accl") && isLoggedIn);
					$(document).data("pageLoadRequest", null);

					$(document).on('keypress', '.search-bar,.secondary-search-bar', function(e) {
						performSearch($(this), e);
					});

					// 36 u befr 17/12/15
					setTimeout(generateMessageBar, 10);
				});
				var performSearch = function(sender, e) {
					if (sender.val().trim() == '' || e.which != 13) {
						return;
					}
					$('.search-bar').val(sender.val());
					var searchContext = 'quran';
                                        var searchPath = 'search';
					if (currentContextJson.search.searchable) {
						searchContext = currentContextJson.id;
                                                searchPath = currentContextJson.search.path;
					} else if (typeof(currentContextJson.search.alternative_search) !== "undefined"){
						searchContext = currentContextJson.search.alternative_search;
					}
					var dblist = typeof(currentContextJson.search.default) !== "undefined" ? "&d=" + currentContextJson.search.default : "";
					// Override for search page
					if (typeof(extraSearchParams) !== "undefined" && $.data(document, 'onSearchPage') === true) {
						dblist = extraSearchParams;
					}
					var searchLink = '/' + searchPath + '/' + searchContext + '?q=' + $('.search-bar').val() + dblist ;
					Utils.fastPageLoad(searchLink);
				};
JS;
	$headHTML .= <<<HEAD
			<link rel='shortcut icon' href='/favicon.png' type='image/png' />
			<link rel='apple-itouch-icon' href='/favicon.png' >
			<link href='//fonts.googleapis.com/css?family=Gentium+Basic:400,700,400italic&amp;subset=latin,latin-ext' rel='stylesheet' type='text/css'>
			<link rel='stylesheet' type='text/css' href='/styles/font/font-awesome-4.5.0/css/font-awesome.min.css' />
			<link rel='stylesheet' type='text/css' href='/styles/font/et-line-font/style.css?v2' />
			<link rel='stylesheet' type='text/css' href='/styles/jquery-ui-1.11.1.custom/jquery-ui.min.css' />
			$cssResources
			$jsResourcesNonCompressed
			$jsResources
			<script>
				$embeddedJS
			</script>
			<a href='#' id='scroll-to-top' title='Scroll to top'><span class='fa fa-chevron-up fa-white'></span></a>
			<title>$opt->title</title>
	</head>
HEAD;
	return $headHTML;
}

function body($opt)
{
    $config = Config::getInstance();
    $contextReflection = new ReflectionClass("Context");
    $currentContextJson = json_decode(Context::getCurrent());
    $constants = $contextReflection->getConstants();
    $constants[] = '{"id":"bookmarks","href":"/bookmarks","label":"Bookmarks","poml":true,"pomm":true,"poms":true,"custom_class":"transition","nofollow":true,"search":{"searchable":false}}';
    $constants[] = '{"id":"memorization","href":"/memorization","label":"Memorization","poml":true,"pomm":true,"poms":true,"custom_class":"transition","nofollow":true,"search":{"searchable":false}}';
    $constants[] = '{"id":"revision","href":"/revision","label":"Revision","poml":true,"pomm":true,"poms":true,"custom_class":"transition","nofollow":true,"search":{"searchable":false}}';
    $constants[] = '{"id":"collections","href":"/mycollections","label":"Collections","poml":true,"pomm":true,"poms":true,"custom_class":"transition","nofollow":true,"search":{"searchable":false}}';
    $contextBar = "";
	// Top links
    $contextLinks = "";
    foreach ($constants as $constantItem) {
        $contextItemJson = json_decode($constantItem);
        $localClass = property_exists($contextItemJson, "custom_class") ? $contextItemJson->custom_class : "";
        $noFollowStr = property_exists($contextItemJson, "nofollow") && $contextItemJson->nofollow == "true" ? " rel='nofollow'" : "";
        $selectedClass = $contextItemJson->id === $currentContextJson->id ? "selected-context" : "";

        $hideOnLarge = $contextItemJson->poml === true;
        $hideOnMedium = $contextItemJson->pomm === true;
        $hideOnSmall = $contextItemJson->poms === true;

        if ($hideOnLarge) {
            $locationClass = "context-on-menu";
        } else if ($hideOnMedium) {
            $locationClass = "context-on-large";
        } else if ($hideOnSmall) {
            $locationClass = "context-on-large context-on-medium";
        } else {
            $locationClass = "context-always";
        }
        if (property_exists($contextItemJson, "suppress") && $contextItemJson->suppress) {
            continue;
        } else if (property_exists($contextItemJson, "hr")) {
            continue;
        } else if ($contextItemJson->label == "") {
            continue;
        } else {
            $contextLinks .= "<a class='context-link context-menu-$contextItemJson->id $selectedClass $localClass $locationClass' $noFollowStr href='$contextItemJson->href'>$contextItemJson->label</a>";
        }
    }
	// .. and More menu
    $contextLinks .= "<a class='more-menu-link'><span class='fa fa-bars fa-white'></span></a>";
	// "More" menu items
    $moreLinks = "";
    foreach ($constants as $constantMenuItem) {
        $contextMenuItemJson = json_decode($constantMenuItem);
        $localClass = property_exists($contextMenuItemJson, "custom_class") ? $contextMenuItemJson->custom_class : "";
        $noFollowStr = property_exists($contextMenuItemJson, "nofollow") && $contextMenuItemJson->nofollow == "true" ? " rel='nofollow'" : "";
        $selectedClass = $contextMenuItemJson->id === $currentContextJson->id ? " context-list-a-hover" : "";

        $hideOnLarge = $contextMenuItemJson->poml === true;
        $hideOnMedium = $contextMenuItemJson->pomm === true;
        $hideOnSmall = $contextMenuItemJson->poms === true;

        if ($hideOnLarge) {
            $locationClass = "context-on-menu";
        } else if ($hideOnMedium) {
            $locationClass = "context-on-large";
        } else if ($hideOnSmall) {
            $locationClass = "context-on-large context-on-medium";
        } else {
            $locationClass = "context-always";
        }
        if (property_exists($contextMenuItemJson, "suppress") && $contextMenuItemJson->suppress) {
            continue;
        } else if (property_exists($contextMenuItemJson, "hr")) {
            $moreLinks .= "<li class='$locationClass'><hr class='menuhr'/></li>";
        } else if ($contextMenuItemJson->label == "") {
            continue;
        } else {
            $moreLinks .= "<li class='$locationClass'><a class='more-context-link context-more-menu-$contextMenuItemJson->id $selectedClass $localClass' $noFollowStr href='$contextMenuItemJson->href'>$contextMenuItemJson->label</a></li>";
        }
    }
	$amraynDotCom = <<<LOGO
		<span class="amrayn-dot-com">m<span>u</span>flihûn.com</span>
LOGO;
    $bodyHTML = <<<BODY
	<body>
	<div id='header-bar' class='body-head'>
            <img alt='Amrayn.com' src='https://cdn.amrayn.com/assets/images/logo-small.png?v=5' class='small-logo'/>
	    <img class='large-logo' src="https://cdn.amrayn.com/assets/images/logo-main.png?v=5" style="position: relative;top: 10px;left: 10px;" id="logohq" class="logohq large" />

        <div class='search-bar-container corner-search-bar'>
            <input type='text' class='rounded search search-bar' style='width:250px; height: 2em;' name='q' placeholder='Search' />
        </div>
        <div class='context-list'>
            $contextLinks
        </div>
        <div class='more-menu'>
            <ul>
                $moreLinks
            </ul>
        </div>
        <div class='search-bar-container full-search-bar'>
            <input type='text' class='rounded search search-bar' style='width:250px; height: 2em;' name='q' placeholder='Search' />
        </div>
	</div>
	</div>
	<div id='nprog'></div>
	<div id='body-contents'>
BODY;
    if ($opt->is_amp) {
        //$bodyHTML = str_replace("<img ", "<amp-img ", $bodyHTML);
    }
    return $bodyHTML;
}
function foot()
{
	$config = Config::getInstance();
	$footHTML = "";
	if (!isset($_GET["__json__"]) && !Debug::on()) {
		$featureFootParam = "feature=foot";
		$thisYear = date('Y');
		$footHTML = <<<FOOT
		</div>
		<div id='foot-contents'>
		    <div class='topbar'>
		        <a href='/' style='text-decoration:none;border: 0;' rel='nofollow'><img src="https://cdn.amrayn.com/assets/images/logo-foot.png?v=$config->IMG_VERSION" class='logo-foot logohq'></a>
		        <div class='links'>
                    <a href='/privacy' class='local foot-links' style='text-decoration: none;'>Privacy</a>
                    <a href='/copyright' class='local foot-links' style='text-decoration: none;'>Copyright</a>
                    <a href='/contribute' class='local foot-links hide-on-really-small-devices' style='text-decoration: none;'>Contribute</a>
                </div>
                <div class='social'>
                <a class='foot-a' title='Like our facebook page' href='https://www.facebook.com/amraynofficial/' rel='noopener' target='_blank'><span class="fa fa-facebook"><span></span></span></a>
                    <a class='foot-a' title='Follow us on twitter' href='https://twitter.com/amraynofficial/' rel='noopener' target='_blank'><span class="fa fa-twitter"><span></span></span></a>
                    <a class='foot-a local' title='About Amrayn.com' href='/about' ><span class="fa fa-info-circle"><span></span></span></a>
                    <a class='foot-a' title='Issue Tracker' href='https://github.com/amrayn/planner/issues' rel='noopener' target='_blank'><span class="fa fa-github"><span></span></span></a>

                </div>
			</div>
            <div class='popular-links'>
                <div class='popular-link-section'>
                    <div class='popular-link-head'>
                        <a href='/1?$featureFootParam'>Popular</a>
                    </div>
                    <div class='popular-link-list'>
                    	<a href='/36?$featureFootParam' class='local popular-link'>Surah Yaseen</a>
                    	<a href='/56?$featureFootParam' class='local popular-link'>Surah al-Waqiyah</a>
                    	<a href='/32?$featureFootParam' class='local popular-link'>Surah as-Sajdah</a>
                    	<a href='/2/255?$featureFootParam' class='local popular-link'>Ayat al-Kursi</a>
                    </div>
                </div>
                <div class='popular-link-section'>
                    <div class='popular-link-head'>
                        <a href='/1?$featureFootParam'>Quran</a>
                    </div>
                    <div class='popular-link-list'>
                      <a href='/quran/info/1?$featureFootParam' class='local popular-link'>Surah Info</a>
                      <a href='/1?$featureFootParam' class='local popular-link'>Web App</a>
                      <a href='/quran/1?$featureFootParam' class='local popular-link'>Medina Mushaf</a>
                      <a href='/memorization?$featureFootParam' class='local popular-link' rel='nofollow'>Memorization Tracker</a>
                      <a href='/revision?$featureFootParam' class='local popular-link' rel='nofollow'>Revision Assistant</a>
                    </div>
                </div>
                <div class='popular-link-section'>
                    <div class='popular-link-head'>
                        <a href='/hadith?$featureFootParam'>Hadith</a>
                    </div>
                    <div class='popular-link-list'>
                        <a href='/bukhari?$featureFootParam' class='local popular-link'>Sahih Bukhari</a>
                        <a href='/muslim?$featureFootParam' class='local popular-link'>Sahih Muslim</a>
                        <a href='/nawawi?$featureFootParam' class='local popular-link'>Arba'in Nawawi</a>
                        <a href='/riyadussaliheen?$featureFootParam' class='local popular-link'>Riyaad us-Saliheen</a>
                        <a href='/hadith?$featureFootParam' class='local popular-link' rel='nofollow'>More...</a>
                    </div>
                </div>
                <!--<div class='popular-link-section'>
                    <div class='popular-link-head'>
                        <a href='/audio?$featureFootParam'>Audio</a>
                    </div>
                    <div class='popular-link-list'>
                        <a href='/audio/quran-recitations?$featureFootParam' class='local popular-link'>Quran</a>
                        <a href='/audio/adhaan?$featureFootParam' class='local popular-link'>Adhaan</a>
                        <a href='/audio/muhammad-tim-humble/aqeedah-tawheed?$featureFootParam' class='local popular-link'>Tawheed</a>
                        <a href='/audio/listen/dr-bilal-philips/patience?$featureFootParam' class='local popular-link'>Patience</a>
                        <a href='/audio/listen/sheikh-salih-al-fawzaan/the-happy-life?$featureFootParam' class='local popular-link'>The Happy Life</a>
                        <a href='/audio?$featureFootParam' class='local popular-link' rel='nofollow'>More...</a>
                    </div>
                </div>-->
                <div class='popular-link-section'>
                    <div class='popular-link-head'>
                        <a href='/articles?$featureFootParam'>Articles</a>
                    </div>
                    <div class='popular-link-list'>
                    	<a href='/articles/category/aqeedah?$featureFootParam' class='local popular-link'>Aqeedah</a>
                        <a href='/articles/category/the-hereafter?$featureFootParam' class='local popular-link'>The Hereafter</a>
                        <a href='/articles/category/matters-of-unseen?$featureFootParam' class='local popular-link'>Matters of Unseen</a>
                        <a href='/articles/ruqya?$featureFootParam' class='local popular-link'>Curing Black Magic</a>
                        <a href='/articles?$featureFootParam' class='local popular-link' rel='nofollow'>More...</a>
                    </div>
                </div>
                <!--<div class='popular-link-section'>
                    <div class='popular-link-head'>
                        <a href='/books?$featureFootParam'>Books</a>
                    </div>
                    <div class='popular-link-list'>
                    	<a href='/books/category/quran?$featureFootParam' class='local popular-link'>Quran</a>
                        <a href='/books/category/hadith?$featureFootParam' class='local popular-link'>Hadith</a>
                        <a href='/books/category/aqeedah?$featureFootParam' class='local popular-link'>Aqeedah</a>
                        <a href='/books/category/seerah?$featureFootParam' class='local popular-link'>Seerah</a>
                        <a href='/books?$featureFootParam' class='local popular-link' rel='nofollow'>More...</a>
                    </div>
                </div>-->
            </div>
			<div class='bottombar'>
				<div>&copy; 2014-$thisYear Amrayn.com</div>
			</div>
		</div>
	</body>
</html>
FOOT;
	}

	return $footHTML;
}


?>
