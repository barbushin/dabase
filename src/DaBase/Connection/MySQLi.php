<?php namespace DaBase\Connection;

use mysqli as PHPMySQLi;

/**
 *
 * @desc This class provides connection to MySQL using deprecated driver http://php.net/manual/ref.mysql.php
 * @see https://github.com/barbushin/dabase
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 *
 * @method PHPMySQLi getConnection()
 */
class MySQLi extends \DaBase\Connection {

	protected function initHelper() {
		return new Helper\MySQL($this);
	}


	protected function initConnection() {
		$mysqli = @new PHPMySQLi(($this->persistent ? 'p:' : '') . $this->host, $this->user, $this->password, $this->dbName, $this->port);

		if($mysqli->connect_error) {
			throw new \DaBase\ConnectionFailed("Connection to {$this->host}:{$this->port}/{$this->dbName} failed with error: {$mysqli->connect_error} [{$mysqli->connect_errno}]");
		}

		if($this->charset) {
			$mysqli->set_charset($this->charset);
		}
		return $mysqli;
	}

	protected function execQuery($preparedSql) {
		return $this->getConnection()->query($preparedSql);
	}

	protected function getLastExecError() {
		return $this->getConnection()->error;
	}

	protected function fetchNextRow($result) {
		/** @var \mysqli_result $result */
		return $result->fetch_assoc();
	}

	protected function quoteString($string) {
		return $this->getConnection()->real_escape_string($string);
	}

	public function getLastInsertId() {
		return $this->getConnection()->insert_id;
	}
}
