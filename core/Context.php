<?php
class Context
{
  const Quran = '{"id":"quran","href":"/","label":"Quran","poml":false,"pomm":false,"poms":false,"search":{"searchable":true,"default":"1,8259,8260,8122,8123,8124,8100","path":"search"}}';
  const Hadith = '{"id":"hadith","href":"/hadith","label":"Hadith","poml":false,"pomm":false,"poms":false,"search":{"searchable":true,"path":"search"}}';
  const Audio = '{"id":"audio","href":"/audio","label":"Audio","poml":true,"pomm":true,"poms":true,"search":{"searchable":false,"path":"search"}}';
  const Articles = '{"id":"articles","href":"/articles","label":"Articles","poml":false,"pomm":false,"poms":true,"search":{"searchable":true,"path":"search"}}';
  const Books = '{"id":"books","href":"/books","label":"Books","poml":true,"pomm":true,"poms":true,"search":{"searchable":false,"path":"search"}}';
  const SupportUs = '{"id":"contribute","href":"/contribute","label":"Contribute","poml":true,"pomm":true,"poms":true,"search":{"searchable":false}}';
  const About = '{"id":"about","href":"/about","label":"About","poml":true,"pomm":true,"poms":true,"search":{"searchable":false}}';
  const More = '{"id":"more","href":"","label":"","poml":true,"pomm":true,"poms":true,"search":{"searchable":false}}';

	private static $currentContext = Context::Quran;
	public static $defaultContext = Context::Quran;

	public static function setCurrent($context)
	{
		Context::$currentContext = $context;
	}

	public static function getCurrent()
	{
		return Context::$currentContext;
	}

	public static function determineFromString($str)
	{
		$contextReflection = new ReflectionClass("Context");
		$constants = $contextReflection->getConstants();
		foreach ($constants as &$constant) {
			$contextJson = json_decode($constant);
			if ($contextJson->id === $str) {
				return $constant;
			}
		}
		return null;
	}
	public static function getAll()
	{
		$contextReflection = new ReflectionClass("Context");
		return $contextReflection->getConstants();
	}
}
?>
