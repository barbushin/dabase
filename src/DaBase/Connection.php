<?php namespace DaBase;

/**
 *
 * @desc This class provides connection to data base, query preparing and fetching
 * @see https://github.com/barbushin/dabase
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 *
 */
abstract class Connection {

	protected $dbName;
	protected $router;
	protected $quoteName = '`';
	protected $quoteValue = '';
	protected $transactionsStarted = 0;
	protected $debugCallback;
	protected $cache;
	protected $cacheDisabled;

	const PRE_NOT_QUOTED_VALUE = '!';
	const PRE_NOT_QUOTED_VALUES = ',!';
	const PRE_QUOTED_VALUE = '?';
	const PRE_QUOTED_VALUES = ',?';
	const PRE_NAME = '#';
	const PRE_NAMES = ',#';
	const PRE_EQUALS = ',=';
	const PRE_AS_IS = '$';

	public function __construct($host, $login, $password, $dbName, $persistent = false, Router $router = null) {
		$this->connect($host, $login, $password, $dbName, $persistent);

		if(!$router) {
			$router = new Router();
		}
		$this->router = $router;
	}

	public function setClientCharset($charset) {
	}

	public function getRouter() {
		return $this->router;
	}

	abstract protected function connect($host, $dbName, $login, $password, $persistent);

	public function query($prepareSql) {
		$replacers = array_slice(func_get_args(), 1);
		return $this->exec($this->prepareSql($prepareSql, $replacers));
	}

	public function exec($preparedSql, $updateCache = true) {
		$start = microtime(true);
		$result = $this->execQuery($preparedSql);
		$time = (microtime(true) - $start) * 1000;

		if(!$result) {
			$this->debugSql($preparedSql, $time);
			if($this->transactionsStarted) {
				$this->rollback();
			}
			throw new \DaBase\QueryFailed('SQL: ' . $preparedSql . ' FAILED WITH ERROR: ' . $this->getLastExecError());
		}

		if($updateCache) {
			$this->updateCache($preparedSql);
		}

		$this->debugSql($preparedSql, $time);
		return $result;
	}

	abstract protected function execQuery($preparedSql);

	abstract protected function getLastExecError();

	abstract protected function fetchNextRow($result);

	public function fetchPreparedSql($preparedSql, $oneColumn = false, $oneRow = false, $keyValue = false) {
		$sqlCacheKey = $preparedSql . '#' . (int)$oneColumn . (int)$oneRow;

		$cached = $this->getFromCache($sqlCacheKey);
		if($cached !== false) {
			$this->debugSql($preparedSql, 0);
			return $cached;
		}

		$fetched = $oneRow ? null : array();
		$result = $this->exec($preparedSql, false);
		while($row = $this->fetchNextRow($result)) {
			if($oneColumn) {
				if($oneRow) {
					$fetched = reset($row);
					break;
				}
				$fetched[] = reset($row);
			}
			elseif($oneRow) {
				$fetched = $row;
				break;
			}
			elseif($keyValue) {
				$fetched[current($row)] = next($row);
			}
			else {
				$fetched[] = $row;
			}
		}

		$this->saveToCache($sqlCacheKey, $fetched);
		return $fetched;
	}

	public function fetch($prepareSql) {
		$replacers = array_slice(func_get_args(), 1);
		return $this->fetchPreparedSql($this->prepareSql($prepareSql, $replacers));
	}

	public function fetchRow($prepareSql) {
		$replacers = array_slice(func_get_args(), 1);
		return $this->fetchPreparedSql($this->prepareSql($prepareSql, $replacers), false, true);
	}

	public function fetchColumn($prepareSql) {
		$replacers = array_slice(func_get_args(), 1);
		return $this->fetchPreparedSql($this->prepareSql($prepareSql, $replacers), true, false);
	}

	public function fetchCell($prepareSql) {
		$replacers = array_slice(func_get_args(), 1);
		return $this->fetchPreparedSql($this->prepareSql($prepareSql, $replacers), true, true);
	}

	public function fetchKeyValue($prepareSql) {
		$replacers = array_slice(func_get_args(), 1);
		return $this->fetchPreparedSql($this->prepareSql($prepareSql, $replacers), false, false, true);
	}

	/**************************************************************
	QUOTERS
	 **************************************************************/

	public function quote($string, $withQuotes = true) {
		if((!is_scalar($string) && !is_null($string)) || (is_object($string) && !method_exists($string, '__toString'))) {
			throw new Exception('Trying to quote "' . gettype($string) . '". Value: "' . var_export($string, true) . '"');
		}
		if(is_bool($string)) {
			$string = (int)$string;
		}
		elseif($string === '' || $string === null) {
			return 'NULL';
		}
		elseif(is_numeric($string)) {
			return $string;
		}
		return $withQuotes ? $this->quoteValue . $this->quoteString($string) . $this->quoteValue : $this->quoteString($string);
	}

	abstract protected function quoteString($string);

	public function quoteArray(array $values, $withQuotes = true) {
		foreach($values as &$value) {
			$value = $this->quote($value, $withQuotes);
		}
		return $values;
	}

	public function quoteName($name) {
		if(!is_scalar($name)) {
			throw new Exception('Trying to quote "' . gettype($name) . '" as name. Value: "' . var_export($name, true) . '"');
		}
		if(!preg_match('/^[\d\w_]+$/', $name)) {
			throw new Exception('Wrong name "' . $name . '" given to quote');
		}
		return $this->quoteName . $name . $this->quoteName;
	}

	public function quoteNames(array $names) {
		foreach($names as &$name) {
			$name = $this->quoteName($name);
		}
		return $names;
	}

	public function quoteEquals(array $fieldsValues, $implode = false) {
		$equals = array();
		foreach($fieldsValues as $field => $value) {
			$equals[] = $this->quoteName($field) . ' = ' . $this->quote($value);
		}
		return $implode ? implode($implode, $equals) : $equals;
	}

	public function sql($sql) {
		$replacers = array_slice(func_get_args(), 1);
		return $this->prepareSql($sql, $replacers);
	}

	public function prepareSql($prepareSql, array $replacers = array()) {
		static $preRegexp;
		if(!$preRegexp) {
			$preRegexp = implode('|', array_map('preg_quote', array(self::PRE_NOT_QUOTED_VALUES, self::PRE_NOT_QUOTED_VALUE, self::PRE_QUOTED_VALUES, self::PRE_QUOTED_VALUE, self::PRE_NAMES, self::PRE_NAME, self::PRE_EQUALS, self::PRE_AS_IS)));
		}

		$splitedSql = preg_split('/(' . $preRegexp . ')/', $prepareSql, -1, PREG_SPLIT_DELIM_CAPTURE);
		if(count($replacers) != (count($splitedSql) - 1) / 2) {
			throw new Exception('Count of replacers in prepare SQL "' . $prepareSql . '" mismatch');
		}
		if($replacers) {
			$preparedSql = '';
			foreach($splitedSql as $i => $p) {
				if($i % 2) {
					$pos = ($i - 1) / 2;
					if($p == self::PRE_QUOTED_VALUE) {
						$p = $this->quote($replacers[$pos]);
					}
					elseif($p == self::PRE_NAME) {
						$p = $this->quoteName($replacers[$pos]);
					}
					elseif($p == self::PRE_NOT_QUOTED_VALUE) {
						$p = $this->quote($replacers[$pos], false);
					}
					elseif($p == self::PRE_NOT_QUOTED_VALUES) {
						$p = implode(', ', $this->quoteArray($replacers[$pos], false));
					}
					elseif($p == self::PRE_QUOTED_VALUES || $p == self::PRE_NAMES) {
						$p = implode(', ', $p == self::PRE_QUOTED_VALUES ? $this->quoteArray($replacers[$pos]) : $this->quoteNames($replacers[$pos]));
					}
					elseif($p == self::PRE_EQUALS) {
						$p = implode(', ', $this->quoteEquals($replacers[$pos]));
					}
					elseif($p == self::PRE_AS_IS) {
						$p = $replacers[$pos];
					}
				}
				$preparedSql .= $p;
			}
			return $preparedSql;
		}
		else {
			return $prepareSql;
		}
	}

	public function getSqlInfo($sql) {
		$type = preg_match('/^\s*(\w+)/s', $sql, $m) ? strtolower($m[1]) : null;

		if($type == 'select') {
			if(preg_match('/[^' . $this->quoteName . $this->quoteValue . ']FROM\s*(,?\s*?' . $this->quoteName . '\w+' . $this->quoteName . '(\s+as\s+' . $this->quoteName . '\w+' . $this->quoteName . ')?)+/is', $sql, $m) && preg_match_all('/' . $this->quoteName . '(\w+)' . $this->quoteName . '(\s+as\s+' . $this->quoteName . '\w+' . $this->quoteName . ')?/is', $m[0], $m)) {
				$tables = $m[1];
				if(preg_match_all('/[^' . $this->quoteName . '\']JOIN\s*' . $this->quoteName . '(\w+)' . $this->quoteName . '/is', $sql, $m)) {
					$tables = array_merge($tables, $m[1]);
				}
				return array('type' => $type, 'tables' => $tables);
			}
		}
		elseif($type == 'insert') {
			if(preg_match('/^INSERT\s+INTO\s+' . $this->quoteName . '(\w+)/is', $sql, $m)) {
				return array('type' => $type, 'table' => $m[1]);
			}
		}
		elseif($type == 'update') {
			if(preg_match('/^UPDATE\s+' . $this->quoteName . '(\w+)/is', $sql, $m)) {
				return array('type' => $type, 'table' => $m[1]);
			}
		}
		elseif($type == 'delete') {
			if(preg_match('/^DELETE\s+FROM\s+' . $this->quoteName . '(\w+)/is', $sql, $m)) {
				return array('type' => $type, 'table' => $m[1]);
			}
		}
		elseif($type) {
			return array('type' => $type);
		}

		return false;
	}

	/**************************************************************
	CACHE
	 **************************************************************/

	public function enableCache() {
		if($this->cacheDisabled) {
			$this->cacheDisabled--;
		}
	}

	public function disableCache() {
		$this->cacheDisabled++;
	}

	public function setCache(Cache $cache) {
		$this->cache = $cache;
	}

	public function updateCache($sql) {
		if($this->cache) {
			$info = $this->getSqlInfo($sql);
			if(in_array($info['type'], array('update', 'insert', 'delete'))) {
				$this->cache->clearByTag($info['table']);
			}
		}
	}

	protected function getFromCache($key) {
		return !$this->cacheDisabled && $this->cache ? $this->cache->get($key) : false;
	}

	protected function saveToCache($sqlCacheKey, $data) {
		if(!$this->cacheDisabled && $this->cache) {
			$info = $this->getSqlInfo($sqlCacheKey);
			if($info['type'] == 'select') {
				$this->cache->add($sqlCacheKey, $data, $info['tables']);
			}
		}
	}

	/**************************************************************
	CUTOM METHODS
	 **************************************************************/

	abstract public function sqlInsert($table, array $data);

	abstract public function sqlLimitOffset($limit, $offset = 0);

	abstract public function sqlRandomFunction();

	public function getLastInsertId() {
		return mysql_insert_id($this->dbLink);
	}

	public function getTables() {
		return $this->fetchColumn('SHOW TABLES');
	}

	public function truncateTable($tableName) {
		return $this->query('TRUNCATE TABLE #', $tableName);
	}

	public function getTableFields($tableName) {
		return $this->fetchColumn('SHOW FIELDS FROM ' . $this->quoteName($tableName));
	}

	public function setDebugCallback($callback) {
		if(!is_callable($callback)) {
			throw new Exception('Debug callback is not callable');
		}
		$this->debugCallback = $callback;
	}

	protected function debugSql($sql, $milisec) {
		if($this->debugCallback) {
			call_user_func_array($this->debugCallback, array($sql, $milisec));
		}
	}

	/**************************************************************
	GETTERS MAGICS AND ROUTER
	 **************************************************************/

	/**
	 * @param string $collectionAlias
	 * @return Collection
	 */
	public function __get($collectionAlias) {
		return $this->router->getCollectionByAlias($collectionAlias, $this);
	}

	public function __call($collectionAlias, $idInArray) {
		return $this->router->getCollectionByAlias($collectionAlias, $this)->getObjectById(reset($idInArray));
	}

	/**************************************************************
	TRANSACTIONS
	 **************************************************************/

	public function begin() {
		if(!$this->transactionsStarted) {
			$this->exec('BEGIN');
		}
		$this->transactionsStarted++;
		return $this;
	}

	public function commit() {
		if(!$this->transactionsStarted) {
			throw new Exception('Trying to commit not existed transaction');
		}
		$this->transactionsStarted--;
		if(!$this->transactionsStarted) {
			$this->exec('COMMIT');
		}
		return $this;
	}

	public function rollback() {
		if(!$this->transactionsStarted) {
			throw new Exception('Trying to rollback not existed transaction');
		}
		$this->transactionsStarted = 0;
		$this->exec('ROLLBACK');
		return $this;
	}

	abstract protected function closeConnection();

	public function __destruct() {
		$this->closeConnection();
	}
}

class ConnectionFailed extends Exception {

}

class QueryFailed extends Exception {

}

