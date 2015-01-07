<?php namespace DaBase\Connection;

/**
 *
 * @desc This class provides connection to MySQL using deprecated driver http://php.net/manual/ref.mysql.php
 * @see https://github.com/barbushin/dabase
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 *
 */
class MySQLnd extends \DaBase\Connection {

	protected function initHelper() {
		return new Helper\MySQL($this);
	}

	protected function initConnection() {
		$connection = $this->persistent
			? @mysql_pconnect($this->host . ':' . $this->port, $this->user, $this->password)
			: @mysql_connect($this->host . ':' . $this->port, $this->user, $this->password, true);

		if(!$connection) {
			throw new \DaBase\ConnectionFailed("Connection to {$this->host}:{$this->port}/{$this->dbName} failed with error: " . mysql_error());
		}
		if(!mysql_select_db($this->dbName, $connection)) {
			throw new \DaBase\ConnectionFailed('Could not select DB: ' . mysql_error() . ' [' . mysql_errno() . ']');
		}
		if($this->charset) {
			mysql_set_charset($this->charset, $connection);
		}
		return $connection;
	}

	protected function execQuery($preparedSql) {
		return mysql_query($preparedSql, $this->getConnection());
	}

	protected function getLastExecError() {
		return mysql_error($this->getConnection()) . ' [' . mysql_errno($this->getConnection()) . ']';
	}

	protected function fetchNextRow($result) {
		return mysql_fetch_assoc($result);
	}

	protected function quoteString($string) {
		return mysql_real_escape_string($string, $this->getConnection());
	}

	public function getLastInsertId() {
		return mysql_insert_id($this->getConnection());
	}
}
