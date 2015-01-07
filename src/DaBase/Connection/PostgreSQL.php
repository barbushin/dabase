<?php namespace DaBase\Connection;

/**
 *
 * @desc This class provides connection with PostgreSQL using http://php.net/pgsql
 * @see https://github.com/barbushin/dabase
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 *
 */
class PostgreSQL extends \DaBase\Connection {

	protected function initConnection() {
		$path = "host={$this->host} dbname={$this->dbName} user={$this->user} password={$this->password} port={$this->port}";
		$connection = $this->persistent ? @pg_pconnect($path) : @pg_connect($path);
		if(!$connection) {
			throw new \DaBase\ConnectionFailed("Connection to {$this->host}:{$this->port}/{$this->dbName} failed with error: " . mysql_error());
		}
		if($this->charset) {
			pg_set_client_encoding($connection, $this->charset);
		}
		return $connection;
	}

	protected function initHelper() {
		return new Helper\PostgreSQL($this);
	}

	protected function execQuery($preparedSql) {
		return @pg_query($this->getConnection(), $preparedSql);
	}

	protected function getLastExecError() {
		return pg_result_error($this->lastResult) ? : pg_last_error($this->getConnection());
	}

	protected function fetchNextRow($result) {
		return pg_fetch_assoc($result);
	}

	protected function quoteString($string) {
		return pg_escape_string($string);
	}

	public function getLastInsertId() {
		$lastId = pg_last_oid($this->lastResult);
		if(!$lastId) {
			$resultRow = $this->fetchNextRow($this->lastResult);
			$lastId = $resultRow['id'];
		}
		return  $lastId;
	}
}
