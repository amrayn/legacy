<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");

class Debug
{
	const Password = "hhsc";

    public static function on()
    {
        return isset($_GET["debug"]) && $_GET["debug"] == Debug::Password;
    }

    public static function setOn()
    {
        $_GET["debug"] = Debug::Password;
    }

	public static function activateReporting() 
	{
		$reporting = 1;
		error_reporting(0);
		ini_set("display_startup_errors", $reporting);
		ini_set("display_errors", $reporting);
		error_reporting(E_ALL ^ E_DEPRECATED);
	}
	
	public static function log($msg, $plain = false) 
	{
		if (Debug::on()) {
			if (is_array($msg)) {
				if (!$plain) {
					echo "<pre>\n";
				} else {
					echo "\n";
				}
				print_r($msg);
				if (!$plain) {
					echo "\n</pre>";
				} else {
					echo "\n";
				}
			} if (is_string($msg)) {
				if (!$plain) {
					echo "<pre>\n";
				} else {
					echo "\n";
				}
				echo($msg);
				if (!$plain) {
					echo "\n</pre>";
				} else {
					echo "\n";
				}
			} else {
				if (!$plain) {
					echo "<pre>\n";
				} else {
					echo "\n";
				}
				var_dump($msg);
				if (!$plain) {
					echo "\n</pre>";
				} else {
					echo "\n";
				}
			}
		}
	}
	
	public static function trace() 
	{
		if (Debug::on()) {
			$trace = debug_backtrace();
			echo "<pre>TRACE: " . $trace[1]["class"] . "::" . $trace[1]["function"] . "</pre>";
		}
	}

    public static function errHandle($errNo, $errStr, $errFile, $errLine) {
        $msg = "$errStr in $errFile on line $errLine";
        if ($errNo == E_NOTICE || $errNo == E_WARNING) {
            throw new ErrorException($msg, $errNo);
        } else {
            Debug::log($msg);
        }
    }
    public static function stopOnFirstError() {
        if (Debug::on()) {
            set_error_handler('Debug::errHandle');
        }
    }
}
if (isset($_GET["report"]) && $_GET["report"] == Debug::Password) {
	Debug::activateReporting();
}
if (Debug::on()) {
	$stack = debug_backtrace();
	$firstFrame = $stack[count($stack) - 1];
	$initialFile = $firstFrame['file'];
	Debug::log("Script: " . $initialFile);
}

?>
