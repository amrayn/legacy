<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("core/queries/ConfigDataQueries.php");
includeOnce("core/utils/Debug.php");

/**
 * When reading simply read key - e.g.,
 * Config::getInstance()->VERSION
 */
class Config {
  public $VERSION = 3;
  public $IMG_VERSION = 3;
  public $QURAN_DEFAULT_ARABIC_FONT = 8;
	public $QURAN_DEFAULT_RECITER = 2;
  public $QURAN_DEFAULT_TRANSLATIONS="8100,0,0,0";
  public $QURAN_DEFAULT_STYLE = 2;
  public $STATIC_IMAGES_BASE = "https://amrayn.com/assets/images";
  public $STATIC_RESOURCES_BASE = "https://amrayn.com/resources";
  public $QURAN_HIDE_ID = "264375";
  public $DOMAIN_SECURE = "https://amrayn.com";
  public $QURAN_DEFAULT_SCRIPT = "1";
  public $QURAN_VERSION = 25;
  public $DOMAIN_VAGUE = "amrayn.com";
  public $ANALYTICS_ENABLED = true;

    protected static $_instance;

    public static function getInstance()
    {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public static function destroy()
    {
        self::$_instance = null;
    }

	private function __construct()
	{
		Debug::trace();
		$allConfigurations = ConfigDataQueries::queryAllActive();
		foreach ($allConfigurations as &$config) {
			$keyName = $config->key;
			$this->$keyName = json_decode($config->value);
		}
		if (isset($_COOKIE["no-analytics"])) {
			$this->ANALYTICS_ENABLED = 0;
		}
	}

	public function asArray($config)
	{
		return explode(",", $config);
	}

	public function asCsv($config)
	{
		$result = array();
		foreach ($config as $key => $val) {
			$result[] = json_encode($val);
		}
		return implode(",", $result);
	}

	public function jsonToJs($config)
	{
		$result = array();
		$i = 1;
		foreach ($config as $key => $val) {
			$result[] = "\"$i\":" . json_encode($val) . "";
			$i++;
		}
		return "{" . implode(",", $result) . ", \"length\":" . ($i - 1) . "}";
	}
}
?>
