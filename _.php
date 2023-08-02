<?php
ob_start("ob_gzhandler");
header_remove('x-powered-by');
header_remove('X-Powered-By');
error_reporting(0);

function root($filename = "")
{
	return $_SERVER["DOCUMENT_ROOT"] . "/" . $filename;
}

function includeOnce($filename)
{
	include_once(root($filename));
}

function includeContents($filename)
{
    if (is_file(root($filename))) {
        ob_start();
        include root($filename);
        $contents = ob_get_contents();
        ob_end_clean();
        return $contents;
    }
    return false;
}
includeOnce("core/Config.php");
includeOnce("core/utils/Debug.php");
includeOnce("core/utils/TokenUtils.php");
includeOnce("core/utils/AccountUtils.php");
AccountUtils::startSession();
?>
