<?php

/**
 *
 * @desc This class provides connection to data base, query preparing and fetching
 * @see http://code.google.com/p/dabase
 * @author Barbushin Sergey http://www.linkedin.com/in/barbushin
 *
 */
class DaBase_Connection_MySQL extends DaBase_Connection {

	protected $connection;

	protected $quoteName = '`';
	protected $quoteValue = '\'';
	protected $charset = 'utf8';

	protected function connect($host, $login, $password, $dbName, $persistent) {
		$this->connection = $persistent ? @mysql_pconnect($host, $login, $password) : @mysql_connect($host, $login, $password);
		if(!$this->connection) {
			throw new DaBase_Exception('Could not connect: ' . mysql_error());
		}
		$this->selectDb($dbName);
		mysql_set_charset($this->charset, $this->connection);
	}

	public function setClientCharset($charset) {
		mysql_set_charset($this->charset, $this->connection);
	}

	public function setTimeZone($timezone) {
		$currentTimeZone = date_default_timezone_get();
		date_default_timezone_set($timezone);
		$this->query('SET `time_zone` = ?', $timezone);
		date_default_timezone_set($currentTimeZone);
	}

	protected function selectDb($dbName) {
		if(!mysql_select_db($dbName, $this->connection)) {
			throw new DaBase_Exception('Could not select DB: ' . $this->getLastExecError());
		}
		$this->dbName = $dbName;
	}

	protected function execQuery($preparedSql) {
		return mysql_query($preparedSql, $this->connection);
	}

	protected function getLastExecError() {
		return mysql_error($this->connection);
	}

	protected function fetchNextRow($result) {
		return mysql_fetch_assoc($result);
	}

	protected function quoteString($string) {
		return mysql_real_escape_string($string, $this->connection);
	}

	public function getLastInsertId() {
		return mysql_insert_id($this->connection);
	}

	public function getTables() {
		return $this->fetchColumn('SHOW TABLES');
	}

	public function getTableFields($tableName) {
		return $this->fetchColumn('SHOW FIELDS FROM ' . $this->quoteName($tableName));
	}

	public function sqlInsert($table, array $data) {
		return 'INSERT INTO ' . $this->quoteName($table) . ($data ? ' SET ' . $this->quoteEquals($data, ', ') : ' VALUES()');
	}

	public function sqlLimitOffset($limit, $offset = 0) {
		$limit = (int)$limit;
		$offset = (int)$offset;
		return 'LIMIT ' . $limit . ($offset ? ' OFFSET ' . $offset : '');
	}

	public function sqlRandomFunction() {
		return 'RAND()';
	}

	protected function closeConnection() {
		if($this->connection) {
			if($this->transactionsStarted) {
				$this->rollback();
			}
			mysql_close($this->connection);
		}
	}
}
