<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("core/queries/EmailQueueQueries.php");
includeOnce("core/utils/UrlUtils.php");
includeOnce("pages/page.php");

$publicIdWithSalt = UrlUtils::paramValue("publicId", "blah");
if (strlen($publicIdWithSalt) > 7) {
    $publicId = substr($publicIdWithSalt, 4, -3);
    $id = EmailQueue::getActualId($publicId);
    $email = EmailQueueQueries::queryActiveById($id);
} else {
    $email = null;
}
$contents = "";
if ($email === null) {
    $contents = "Email has been removed from our system";
    $title = "Email not found";

    init($contents, array(
        "title" => "$title",
        "context" => Context::More
    ));
    die();
} else {
    $title = $email->subject;
    $contents = strlen($email->htmlText) > 0 ? $email->htmlText : $email->text;
    if (strlen($contents) == 0) {
        $contents = "Email has been removed from our system";
        $title = "Email not found";

        init($contents, array(
            "title" => "$title",
            "context" => Context::More
        ));
        die();
    }
}
$embeddedJS = "";
if ($config->ANALYTICS_ENABLED) {

    $embeddedJS = <<<JS
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
					ga('create', '', 'auto');
					ga('require', 'linkid', 'linkid.js');
					ga('require', 'displayfeatures');
				}
				$(document).ready(function() {
					Analytics.pageView();
				});
JS;
}
$jsResources = getJSResources(array(
    "/scripts/Debug.js",
    "/scripts/Analytics.js",
    "/scripts/utils.js"
));
$jsResourcesNonCompressed = getJSResources(array(
    "/scripts/jquery-2.1.0.min.js"
), true);
echo <<<P
<html>
    <head>
        $jsResourcesNonCompressed
        $jsResources
        <script>$embeddedJS</script>
        <title>$title</title>
    </head>
    <body>
        <style>
            .view-online-link {
                display: none !important;
            }
        </style>
        $contents
    </body>
</html>
P;
