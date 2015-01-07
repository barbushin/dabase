<?php namespace DaBase\Connection;

use PDO as PHPPDO;

/**
 * Class PDO
 * @package DaBase\Connection
 * @method PHPPDO getConnection()
 */
class PDO extends \DaBase\Connection {

	protected $pdoDriver;

	/**
	 * @param string $pdoDriver mysql|pgsql
	 * @param $host
	 * @param $dbName
	 * @param string $user
	 * @param string $password
	 * @param bool $persistent
	 * @param null $port
	 * @param string $charset
	 */
	public function __construct($pdoDriver, $host, $dbName, $user, $password = '', $persistent = false, $port = null, $charset = 'utf8') {
		$this->pdoDriver = $pdoDriver;
		parent::__construct($host, $dbName, $user, $password, $persistent, $port, $charset);
	}

	protected function initConnection() {
		$pdoPath = "{$this->pdoDriver}:host={$this->host};port={$this->port};dbname={$this->dbName}";
		try {
			return new PHPPDO($pdoPath, $this->user, $this->password, array(
				PHPPDO::ATTR_PERSISTENT => $this->persistent
			));
		}
		catch(\Exception $exception) {
			throw new \DaBase\ConnectionFailed("Connection to {$pdoPath} failed wih error: {$exception->getMessage()}");
		}
	}

	protected function initHelper() {
		if($this->pdoDriver == 'mysql') {
			return new Helper\MySQL($this);
		}
		elseif($this->pdoDriver == 'pgsql') {
			return new Helper\PostgreSQL($this);
		}
		else {
			throw new \DaBase\Exception("Unknown driver name {$this->pdoDriver}");
		}
	}

	protected function execQuery($preparedSql) {
		return $this->getConnection()->query($preparedSql);
	}

	protected function getLastExecError() {
		$error = $this->getConnection()->errorInfo();
		return "{$error[2]} [$error[1]}";
	}

	protected function fetchNextRow($statement) {
		/** @var \PDOStatement $statement */
		return $statement->fetch(PHPPDO::FETCH_ASSOC);
	}

	public function getLastInsertId() {
		$lastId = $this->getConnection()->lastInsertId();
		if(!$lastId) {
			$lastRow = $this->fetchNextRow($this->lastResult);
			$lastId = $lastRow['id'];
		}
		return $lastId;
	}

	protected function quoteString($string) {
		$quoted = $this->getConnection()->quote($string);
		$quoted = substr($quoted, 1, -1);
		return $quoted;
	}
}
