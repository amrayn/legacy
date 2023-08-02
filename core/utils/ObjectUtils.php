<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("core/models/Object.php");

class ObjectUtils
{

	public static function buildFromArray($arr, $escapeValue = false)
	{
		$obj = new MyObject();
		foreach ($arr as $key => $value) {
			$obj->$key = $escapeValue ? ($value) : $value;
		}
		return $obj;
	}
}
