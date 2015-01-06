<?php namespace DaBase\Connection;

/**
 *
 * @desc This class provides connection to data base, query preparing and fetching
 * @see https://github.com/barbushin/dabase
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 *
 */
class PostgreSQL extends \DaBase\Connection {

	protected $connection;
	protected $lastResult;
	protected $quoteName = '"';
	protected $quoteValue = '\'';

	protected function connect($host, $login, $password, $dbName, $persistent) {
		$connectionString = "host=$host dbname=$dbName user=$login password=$password";
		if($persistent) {
			$this->connection = pg_pconnect($connectionString);
		}
		else {
			$this->connection = pg_connect($connectionString);
		}
		if(!$this->connection) {
			throw new \DaBase\ConnectionFailed('Unable connect to DB');
		}
	}

	protected function execQuery($preparedSql) {
		$this->lastResult = @pg_query($this->connection, $preparedSql);
		return $this->lastResult;
	}

	protected function getLastExecError() {
		return pg_last_error($this->connection);
	}

	protected function fetchNextRow($result) {
		return pg_fetch_assoc($result);
	}

	protected function quoteString($string) {
		return pg_escape_string($string);
	}

	public function getLastInsertId() {
		return pg_last_oid($this->lastResult);
	}

	// TODO: check
	public function getTables() {
		return $this->fetchColumn('SHOW TABLES');
	}

	// TODO: check
	public function getTableFields($tableName) {
		return $this->fetchColumn('SHOW FIELDS FROM ' . $this->quoteName($tableName));
	}

	public function sqlLimitOffset($limit, $offset = 0) {
		$limit = (int)$limit;
		$offset = (int)$offset;
		return 'LIMIT ' . $limit . ($offset ? ' OFFSET ' . $offset : '');
	}

	public function sqlRandomFunction() {
		return 'RANDOM()';
	}

	public function sqlInsert($table, array $data) {
		return 'INSERT INTO ' . $this->quoteName($table) . ($data ? ' (' . implode(', ', $this->quoteNames(array_keys($data))) . ') VALUES (' . implode(', ', $this->quoteArray($data)) . ')' : ' VALUES()');
	}

	protected function closeConnection() {
		if($this->connection) {
			if($this->transactionStarted) {
				$this->rollback();
			}
			pg_close($this->connection);
		}
	}
}
