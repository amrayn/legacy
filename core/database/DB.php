<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("core/utils/StringUtils.php");
includeOnce("core/utils/Debug.php");
includeOnce("core/utils/Timer.php");
class DB {

	protected static $_instance;

	public static function getInstance()
	{
			if (!(self::$_instance instanceof self)) {
					self::$_instance = new self();
			}
			return self::$_instance;
	}

	public function __construct()
	{

		Debug::log("Connecting " . static::getDatabase() . "@" . static::getHost() . " [DB::__construct]");
    $dsn = "mysql:host=" . static::getHost() . ";port=" . static::getPort() . ";dbname=" . static::getDatabase() . ";";
    Debug::log("PDO: " . $dsn);
		try {
			$this->conn = new PDO($dsn, static::getUser(), static::getPassword());
			$this->conn->exec("SET time_zone = '+0:00';");
			$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch(PDOException $e) {
			echo("Failed to connect to the server (Err: 1/CONNECT)" . Debug::on() ? ("<pre>" . $e) : "");
			throw $e;
		}
	}

	public function query($sql, $params = array())
	{
		try {
			$timer = new Timer("DB::query [$sql] => " . implode(",", $params));
			$sth = $this->conn->prepare($sql);
			$sth->setFetchMode(PDO::FETCH_ASSOC);
			$sth->execute($params);
			return $sth->fetchAll();
		} catch (PDOException $e) {
			Debug::log($e->getMessage());
			echo ("Failed to connect to the server (Err: 2/QUERY)");
			throw $e;
		}
		return null;
	}

	public function execute($sql, $params = array())
	{
		$timer = new Timer("DB::execute [$sql] => " . implode(",", $params));
		try {
			$sth = $this->conn->prepare($sql);
			$sth->execute($params);
			if (StringUtils::startsWith(trim($sql), "INSERT INTO")) {
				return $this->conn->lastInsertId();
			}
			return null;
		} catch (PDOException $e) {
			Debug::log($e->getMessage());
			echo ("Failed to connect to the server (Err: 2/QUERY)");
			throw $e;
		}
	}

    public function getDatabase() {
        return "$_ENV[DB_NAME]";
    }

    public function getUser() {
        return "$_ENV[DB_USER]";
    }

    public function getPassword() {
        return "$_ENV[DB_PWD]";
    }

    public function getHost() {
      return "$_ENV[DB_HOST]";
    }

    public function getPort() {
      return "$_ENV[DB_PORT]";
    }
}
