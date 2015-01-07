<?php namespace DaBase;

/**
 *
 * @desc This class provides connection to SQL databases by PDO driver, including query preparing and fetching
 * @see https://github.com/barbushin/dabase
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 *
 */
abstract class Connection {

	const PRE_NAME = '@';
	const PRE_NAMES = ',@';
	const PRE_EQUALS = ',=';
	const PRE_AS_IS = '$';
	const PRE_QUOTED_VALUE = '?';
	const PRE_QUOTED_VALUES = ',?';
	const PRE_NOT_QUOTED_VALUE = '??';
	const PRE_NOT_QUOTED_VALUES = ',??';

	/** @var Router */
	protected $router;
	/** @var \DaBase\Connection\Helper */
	protected $helper;
	/** @var  Cache */
	protected $cache;
	protected $host;
	protected $port;
	protected $dbName;
	protected $user;
	protected $password;
	protected $charset;
	protected $persistent;
	protected $nameQuoteChar;
	protected $valueQuoteChar;
	protected $lastResult;
	protected $transactionsStarted = 0;
	protected $debugCallback;
	protected $cacheDisabled;
	protected $isConnected = false;


	abstract protected function initConnection();

	abstract protected function initHelper();

	abstract protected function execQuery($preparedSql);

	abstract protected function getLastExecError();

	abstract protected function fetchNextRow($result);

	abstract public function getLastInsertId();

	public function __construct($host, $dbName, $user, $password = '', $persistent = false, $port = null, $charset = null) {
		$this->host = $host;
		$this->dbName = $dbName;
		$this->user = $user;
		$this->password = $password;
		$this->charset = $charset;
		$this->persistent = $persistent;
		$this->helper = $this->initHelper();
		$this->port = $port ?: $this->helper->getDefaultPort();
		$this->nameQuoteChar = $this->helper->getNameQuoteChar();
		$this->valueQuoteChar = $this->helper->getValueQuoteChar();
	}

	public function setRouter(Router $router) {
		$this->router = $router;
	}

	public function getRouter() {
		if(!$this->router) {
			$this->router = new Router();
		}
		return $this->router;
	}

	public function getConnection() {
		static $connection;
		if(!$connection) {
			$connection = $this->initConnection();
		}
		return $connection;
	}

	public function getHelper() {
		return $this->helper;
	}

	public function query($prepareSql) {
		$replacers = array_slice(func_get_args(), 1);
		return $this->exec($this->prepareSql($prepareSql, $replacers));
	}

	public function exec($preparedSql, $updateCache = true) {
		$start = microtime(true);
		$result = $this->execQuery($preparedSql);
		$this->lastResult = $result;
		$time = (microtime(true) - $start) * 1000;

		if($result === false) {
			$this->debugSql($preparedSql, $time);
			$error = $this->getLastExecError();
			if($this->transactionsStarted) {
				$this->rollback();
			}
			throw new \DaBase\QueryFailed('SQL: ' . $preparedSql . ' FAILED WITH ERROR: ' . $error);
		}

		if($updateCache) {
			$this->updateCache($preparedSql);
		}

		$this->debugSql($preparedSql, $time);
		return $result;
	}

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
	 * QUOTES
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
		return $withQuotes ? $this->valueQuoteChar . $this->quoteString($string) . $this->valueQuoteChar : $this->quoteString($string);
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
		return $this->nameQuoteChar . $name . $this->nameQuoteChar;
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
					/** @var string $pos */
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
			if(preg_match('/[^' . $this->nameQuoteChar . $this->valueQuoteChar . ']FROM\s*(,?\s*?' . $this->nameQuoteChar . '\w+' . $this->nameQuoteChar . '(\s+as\s+' . $this->nameQuoteChar . '\w+' . $this->nameQuoteChar . ')?)+/is', $sql, $m) && preg_match_all('/' . $this->nameQuoteChar . '(\w+)' . $this->nameQuoteChar . '(\s+as\s+' . $this->nameQuoteChar . '\w+' . $this->nameQuoteChar . ')?/is', $m[0], $m)) {
				$tables = $m[1];
				if(preg_match_all('/[^' . $this->nameQuoteChar . '\']JOIN\s*' . $this->nameQuoteChar . '(\w+)' . $this->nameQuoteChar . '/is', $sql, $m)) {
					$tables = array_merge($tables, $m[1]);
				}
				return array('type' => $type, 'tables' => $tables);
			}
		}
		elseif($type == 'insert') {
			if(preg_match('/^INSERT\s+INTO\s+' . $this->nameQuoteChar . '(\w+)/is', $sql, $m)) {
				return array('type' => $type, 'table' => $m[1]);
			}
		}
		elseif($type == 'update') {
			if(preg_match('/^UPDATE\s+' . $this->nameQuoteChar . '(\w+)/is', $sql, $m)) {
				return array('type' => $type, 'table' => $m[1]);
			}
		}
		elseif($type == 'delete') {
			if(preg_match('/^DELETE\s+FROM\s+' . $this->nameQuoteChar . '(\w+)/is', $sql, $m)) {
				return array('type' => $type, 'table' => $m[1]);
			}
		}
		elseif($type) {
			return array('type' => $type);
		}

		return false;
	}

	/**************************************************************
	 * CACHE
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
	 * CUTOM METHODS
	 **************************************************************/

	public function setDebugCallback($callback) {
		if(!is_callable($callback)) {
			throw new Exception('Debug callback is not callable');
		}
		$this->debugCallback = $callback;
	}

	protected function debugSql($sql, $milliseconds) {
		if($this->debugCallback) {
			call_user_func_array($this->debugCallback, array($sql, $milliseconds));
		}
	}

	/**************************************************************
	 * GETTERS MAGICS AND ROUTER
	 **************************************************************/

	/**
	 * @param string $collectionAlias
	 * @return Collection
	 */
	public function __get($collectionAlias) {
		return $this->getRouter()->getCollectionByAlias($collectionAlias, $this);
	}

	public function __call($collectionAlias, $idInArray) {
		return $this->getRouter()->getCollectionByAlias($collectionAlias, $this)->getObjectById(reset($idInArray));
	}

	/**************************************************************
	 * TRANSACTIONS
	 **************************************************************/

	public function begin() {
		if(!$this->transactionsStarted) {
			$this->transactionBegin();
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
			$this->transactionCommit();
		}
		return $this;
	}

	public function rollback() {
		if(!$this->transactionsStarted) {
			throw new Exception('Trying to rollback not existed transaction');
		}
		$this->transactionsStarted = 0;
		$this->transactionRollback();
		return $this;
	}

	protected function transactionBegin() {
		$this->exec('BEGIN');
	}

	protected function transactionCommit() {
		$this->exec('COMMIT');
	}

	protected function transactionRollback() {
		$this->exec('ROLLBACK');
	}
}

class ConnectionFailed extends Exception {

}

class QueryFailed extends Exception {

}

