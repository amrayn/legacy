<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("core/utils/Debug.php");
class Timer
{
	public function __construct($name) 
	{
		$this->start = microtime(true); 
		$this->name = $name;
	}
	public function __destruct() 
	{
		$this->end = microtime(true);
		Debug::log($this->name . " - Took [" . ($this->end - $this->start) . "] seconds");
	}
}
?>
