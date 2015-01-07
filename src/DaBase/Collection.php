<?php namespace DaBase;

/**
 *
 * @desc This class provides access to database tables
 * @see https://github.com/barbushin/dabase
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 *
 */
class Collection {

	const table = null;
	const objectsClass = null;
	const handlePropertiesOnRead = false;
	const handlePropertiesOnWrite = false;

	protected static $whereSpecialStrings = array('OR', 'XOR', 'AND', '(', ')');
	protected static $statusProperties = array('whereParts', 'whereHasLastOperator', 'limit', 'offset', 'orderBy', 'groupBy', 'appenders', 'beforeWhere', 'postGetHandlers');

	/**
	 * @var Connection
	 */
	protected $db;
	/**
	 * @var Object
	 */
	protected $objectsClass;
	protected $skipNextFilterReset = 0;

	protected $alias;
	protected $table;
	protected $limit;
	protected $offset;
	protected $orderBy;
	protected $groupBy;
	protected $savedStatuses;
	protected $whereParts;
	protected $whereHasLastOperator;
	protected $beforeWhere;
	protected $postGetHandlers;
	protected $appenders;

	public function __construct(Connection $db, $alias, $table, $objectsClass) {
		$this->db = $db;
		if(!$table || !$objectsClass) {
			throw new Exception();
		}
		$this->table = $table;
		$this->alias = $alias;
		$this->setObjectsClass($objectsClass);
		$this->postInit();
	}

	public function setObjectsClass($objectsClass) {
		$this->objectsClass = $objectsClass;
		return $this;
	}

	protected function postInit() {
	}

	public function getDb() {
		return $this->db;
	}

	public function getTable() {
		return $this->table;
	}

	public function getAlias() {
		return $this->alias;
	}

	/**************************************************************
	PROPERTIES DATA HANDLING
	 **************************************************************/

	protected function handlePropertiesOnWrite(array $properties, &$conditionOperand = null) {
		return $properties;
	}

	protected function handlePropertiesOnRead(array $row) {
		return $row;
	}

	/**************************************************************
	COLLECTION CRUD
	 **************************************************************/

	/**
	 * @param array $properties
	 * @return Object
	 */
	public function getNew(array $properties = array()) {
		return new $this->objectsClass($properties);
	}

	public function get($oneObject = false, $throwExceptionIfNotFound = false) {
		if($oneObject && !$this->limit) {
			$this->limit(1);
		}
		$result = $this->fetchPreparedQueryToObjects('SELECT ' . $this->db->quoteName($this->table) . '.* FROM ' . $this->db->quoteName($this->table) . $this->beforeWhere . $this->sqlWhere() . $this->sqlGroupBy() . $this->sqlOrderBy() . $this->sqlLimit(), $oneObject);
		$this->resetFilter();
		return $result;
	}

	public function getByQuery($prepareSql) {
		return $this->fetchPreparedQueryToObjects($this->db->prepareSql($prepareSql, array_slice(func_get_args(), 1)));
	}

	public function getOneByQuery($prepareSql) {
		return $this->fetchPreparedQueryToObjects($this->db->prepareSql($prepareSql, array_slice(func_get_args(), 1)), true);
	}

	protected function fetchPreparedQueryToObjects($preparedSql, $oneObject = false) {
		$objects = array();
		foreach($this->db->fetchPreparedSql($preparedSql) as $i => $row) {
			if(static::handlePropertiesOnRead) {
				$row = $this->handlePropertiesOnRead($row);
			}
			$objects[isset($row['id']) ? $row['id'] : $i] = $this->getNew($row);
		}

		$this->handleAppendersToObjects($objects);
		$this->handleObjectsResult($objects);

		if($oneObject) {
			if(!$objects) {
				return null;
			}
			elseif(count($objects) > 1) {
				throw new Exception('Request returns more than one object');
			}
			return reset($objects);
		}
		return $objects;
	}

	/**
	 * @param $id
	 * @param bool $throwExceptionIfNotFound
	 * @return Object|null
	 * @throws ObjectNotFound
	 */
	public function getObjectById($id, $throwExceptionIfNotFound = true) {
		return $this->filter('id', $id)->getObject($throwExceptionIfNotFound);
	}

	/**
	 * @param bool $throwExceptionIfNotFound
	 * @return Object|null
	 * @throws ObjectNotFound
	 */
	public function getObject($throwExceptionIfNotFound = true) {
		$whereParts = $this->whereParts;
		$object = $this->get(true);
		if(!$object && $throwExceptionIfNotFound) {
			throw new ObjectNotFound('Object not found in table "' . $this->table . '" by criteria: ' . print_r($whereParts, true));
		}
		return $object;
	}

	public function getColumn($valueProperty, $keyProperty = 'id') {
		$founds = array();
		foreach($this->db->fetchPreparedSql('SELECT ' . $this->getPropertyNameSql($keyProperty) . ', ' . $this->getPropertyNameSql($valueProperty) . ' FROM ' . $this->db->quoteName($this->table) . $this->beforeWhere . $this->sqlWhere() . $this->sqlOrderBy() . $this->sqlLimit()) as $row) {
			if(static::handlePropertiesOnRead) {
				$row = $this->handlePropertiesOnRead($row);
			}
			$founds[$row[$keyProperty]] = $row[$valueProperty];
		}
		$this->resetFilter();
		return $founds;
	}

	public function getProperty($property) {
		if(!$this->limit) {
			$this->limit(1);
		}
		$value = $this->db->fetchPreparedSql('SELECT ' . $this->getPropertyNameSql($property) . ' as `value` FROM ' . $this->db->quoteName($this->table) . $this->beforeWhere . $this->sqlWhere() . $this->sqlOrderBy() . $this->sqlLimit(), true, true);
		if(static::handlePropertiesOnRead) {
			list($value) = $this->handlePropertiesOnRead(array($property => $value));
		}
		$this->resetFilter();
		return $value;
	}

	public function count() {
		$count = (int)$this->db->fetchPreparedSql('SELECT COUNT(*) FROM ' . $this->db->quoteName($this->table) . $this->beforeWhere . $this->sqlWhere(), true, true);
		$this->resetFilter();
		return $count;
	}

	public function update($propertiesValuesOrObject, $onlyNotNull = false) {
		if(is_object($propertiesValuesOrObject)) {
			return $this->updateObject($propertiesValuesOrObject, $onlyNotNull);
		}
		return $this->updatePropertiesValues($propertiesValuesOrObject, $onlyNotNull);
	}

	public function insert($objectOrArray, $checkId = true, $skipValidation = false) {
		if(is_array($objectOrArray)) {
			$objectOrArray = $this->getNew($objectOrArray);
		}
		return $this->insertObject($objectOrArray, $checkId, $skipValidation);
	}

	public function updatePropertiesValues(array $propertiesValues, $onlyNotNull = false) {
		$object = $this->getNew($propertiesValues);
		$updateProperties = array_intersect_assoc($object->asArray($onlyNotNull, true), $propertiesValues);
		$object->validate(array_keys($updateProperties));

		if(static::handlePropertiesOnWrite) {
			$updateProperties = $this->handlePropertiesOnWrite($updateProperties);
		}

		$this->db->exec('UPDATE ' . $this->db->quoteName($this->table) . ' SET ' . implode(', ', $this->db->quoteEquals($updateProperties)) . $this->sqlWhere() . $this->sqlOrderBy() . $this->sqlLimit());
		$this->resetFilter();
		return $this;
	}

	public function delete(Object $object = null) {
		if($object) {
			return $this->deleteObject($object);
		}
		return $this->deleteByFilter();
	}

	protected function deleteByFilter() {
		$this->db->exec('DELETE FROM ' . $this->db->quoteName($this->table) . $this->sqlWhere() . $this->sqlOrderBy() . $this->sqlLimit());
		$this->resetFilter();
		return $this;
	}

	/**************************************************************
	OBJECT CRUD
	 **************************************************************/

	public function insertObject(Object $object, $checkId = true, $skipValidation = false) {
		if($checkId && $object->id) {
			throw new Exception('Trying to make insert() of object with not empty "id"');
		}
		if(!$skipValidation) {
			$object->validate();
		}

		$propertiesValues = $object->asArray(true, true);

		if(static::handlePropertiesOnWrite) {
			$propertiesValues = $this->handlePropertiesOnWrite($propertiesValues);
		}

		$this->db->exec($this->db->getHelper()->sqlInsert($this->table, $propertiesValues));
		if(!$object->id) {
			$object->id = $this->db->getLastInsertId();
		}
		return $object;
	}

	public function updateObject(Object $object) {
		$oldObject = $this->getObjectById($object->id);

		$changedPropertiesValues = array();
		foreach($object->asArray(false, true) as $property => $value) {
			if($value != $oldObject->$property) {
				$changedPropertiesValues[$property] = $value;
			}
		}

		if($changedPropertiesValues) {
			$object->validate(array_keys($changedPropertiesValues));
			if(static::handlePropertiesOnWrite) {
				$changedPropertiesValues = $this->handlePropertiesOnWrite($changedPropertiesValues);
			}
			$this->db->exec('UPDATE ' . $this->db->quoteName($this->table) . 'SET ' . implode(', ', $this->db->quoteEquals($changedPropertiesValues)) . ' WHERE id=' . $this->db->quote($object->id));
		}
		return $object;
	}

	public function deleteObject(Object $object) {
		if(!$object->id) {
			throw new Exception('Trying to delete object with empty "id"');
		}
		$this->db->exec('DELETE FROM ' . $this->db->quoteName($this->table) . ' WHERE id=' . $this->db->quote($object->id));
		$object->id = null;
		return $object;
	}

	/**************************************************************
	APPENDING
	 **************************************************************/

	public function append(Collection $collection, $appendProperty = null, $joinChildProperty = null, $joinParentProperty = 'id', $multiple = true, $keyProperty = null) {
		if(!$appendProperty) {
			$appendProperty = $collection->getAlias();
		}
		if(!$joinChildProperty) {
			$joinChildProperty = $this->db->getRouter()->getJoinFieldNameByCollection($this);
		}
		$this->appenders[] = array($collection, $appendProperty, $joinChildProperty, $joinParentProperty, $multiple, $keyProperty);
		return $this;
	}

	protected function handleAppendersToObjects(array $objects) {
		if($objects && $this->appenders) {
			foreach($this->appenders as $appender) {
				list($collection, $appendProperty, $joinChildProperty, $joinParentProperty, $multiple, $keyProperty) = $appender;

				/**
				 * @var $collection Collection
				 */
				if($collection->getLimit() || $collection->getOffset()) {
					throw new Exception('Cannot append _collection with not empty limit or offset');
				}
				$attachProperty = '_' . $appendProperty;
				$parentPropertyValuesAndObjectsIds = array();
				foreach($objects as $id => $object) {
					$parentPropertyValuesAndObjectsIds[$object->$joinParentProperty][] = $id;
					if(!isset($object->$attachProperty)) {
						$object->$attachProperty = $multiple ? array() : null;
					}
				}
				$childObjects = $collection->filter($joinChildProperty, array_keys($parentPropertyValuesAndObjectsIds))->get();
				foreach($childObjects as $childId => $childObject) {
					foreach($parentPropertyValuesAndObjectsIds[$childObject->$joinChildProperty] as $id) {
						if($multiple) {
							$objects[$id]->{$attachProperty}[$keyProperty ? $childObject->$keyProperty : $childId] = $childObject;
						}
						else {
							$objects[$id]->$attachProperty = $childObject;
						}
					}
				}
			}
		}
	}

	/**************************************************************
	GETTER SETTINGS
	 **************************************************************/

	public function ignoreFilterReset() {
		$this->skipNextFilterReset = -999999999;
	}

	public function skipNextFilterReset() {
		$this->skipNextFilterReset++;
		return $this;
	}

	public function resetFilter($property = null) {
		if($this->skipNextFilterReset) {
			$this->skipNextFilterReset--;
			return $this;
		}
		if($property) {
			if(!in_array($property, self::$statusProperties)) {
				throw new Exception('Unknown reset property "' . $property . '"');
			}
			$this->$property = null;
		}
		else {
			foreach(self::$statusProperties as $property) {
				$this->$property = null;
			}
		}
		$this->postInit();
		return $this;
	}

	public function saveStatus() {
		$savedStatus = array();
		foreach(self::$statusProperties as $property) {
			$savedStatus[$property] = $this->$property;
		}
		$this->savedStatuses[] = $savedStatus;
		$this->resetFilter();
	}

	public function loadStatus() {
		foreach(array_pop($this->savedStatuses) as $property => $value) {
			$this->$property = $value;
		}
	}

	public function addObjectsResultHandler($callback) {
		if(!is_callable($callback)) {
			throw new Exception('Callable argument is required');
		}
		$this->postGetHandlers[] = $callback;
	}

	protected function handleObjectsResult(&$result) {
		if($this->postGetHandlers) {
			foreach($this->postGetHandlers as $callback) {
				call_user_func($callback, $result);
			}
		}
	}

	protected function getPropertyNameSql($property) {
		return $this->db->quoteName($this->table) . '.' . $this->db->quoteName($property);
	}

	protected function addWhereCondition($property, $value, $operand = '=') {
		$propertySql = $this->getPropertyNameSql($property);

		$operand = trim($operand);
		if($operand == '!=') {
			$operand = '<>';
		}

		if(static::handlePropertiesOnWrite) {
			$values = $this->handlePropertiesOnWrite(array($property => $value), $operand);
			$value = reset($values);
		}

		if(is_array($value)) {
			if($value || $operand != '<>') {
				$this->addSqlToWhere($value ? ($propertySql . ($operand == '<>' ? ' NOT' : '') . ' IN (' . implode(',', $this->db->quoteArray($value)) . ')') : '0');
			}
		}
		elseif($value === null) {
			$this->addSqlToWhere($propertySql . ' IS ' . ($operand == '<>' ? 'NOT ' : '') . 'NULL');
		}
		else {
			$this->addSqlToWhere($propertySql . ' ' . $operand . ' ' . $this->db->quote($value));
		}

		return $this;
	}

	public function addSqlToWhere($sql) {
		if(!$this->whereHasLastOperator && $this->whereParts) {
			$sql = 'AND ' . $sql;
		}
		else {
			$this->whereHasLastOperator = false;
		}
		$replacers = array_slice(func_get_args(), 1);
		$this->whereParts[] = $replacers ? $this->db->prepareSql($sql, $replacers) : $sql;
		return $this;
	}

	public function filter($property, $value, $operand = '=') {
		$this->addWhereCondition($property, $value, $operand);
		return $this;
	}

	public function isNull($property) {
		return $this->filter($property, null);
	}

	public function isNotNull($property) {
		return $this->filter($property, null, '<>');
	}

	public function addSqlBeforeWhere($sql) {
		$replacers = array_slice(func_get_args(), 1);
		$this->beforeWhere .= ' ' . ($replacers ? $this->db->prepareSql($sql, $replacers) : $sql) . ' ';
		return $this;
	}

	public function sqlWhere() {
		if($this->whereParts) {
			$where = implode(' ', $this->whereParts);
			return ' WHERE ' . $where;
		}
		return '';
	}

	public function orderBy($property, $desc = false, $equalValue = null, $quoteEqualValue = true) {
		return $this->addSqlToOrderBy($this->getPropertyNameSql($property) . ($equalValue ? ' = ' . ($quoteEqualValue ? $this->db->quote($equalValue) : $equalValue) : '') . ($desc ? ' DESC' : ''));
	}

	public function addSqlToOrderBy($sql) {
		$replacers = array_slice(func_get_args(), 1);
		$this->orderBy[] = ($replacers ? $this->db->prepareSql($sql, $replacers) : $sql);
		return $this;
	}

	public function orderByRand() {
		$this->orderBy[] = $this->db->getHelper()->sqlRandomFunction();
		return $this;
	}

	protected function sqlOrderBy() {
		if($this->orderBy) {
			return ' ORDER BY ' . implode(', ', $this->orderBy);
		}
		return '';
	}

	public function groupBy($property, $desc = false) {
		$this->groupBy[] = $this->getPropertyNameSql($property) . ($desc ? ' DESC' : '');
		return $this;
	}

	protected function sqlGroupBy() {
		if($this->groupBy) {
			return ' GROUP BY ' . implode(', ', $this->groupBy);
		}
		return '';
	}

	public function limit($limit, $offset = null) {
		$this->limit = $limit;
		$this->offset = $offset;
		return $this;
	}

	public function limitPage($itemsOnPage, $page = 1) {
		return $this->limit($itemsOnPage, $itemsOnPage * ($page - 1));
	}

	public function getLimit() {
		return $this->limit;
	}

	public function getOffset() {
		return $this->offset;
	}

	protected function sqlLimit() {
		if($this->limit || $this->offset) {
			return ' ' . $this->db->getHelper()->sqlLimitOffset($this->limit, $this->offset);
		}
		return '';
	}

	/**************************************************************
	MAGICS
	 **************************************************************/

	public function __toString() {
		return $this->table;
	}

	public function __get($property) {
		if(strstr($property, ' ') || in_array($property, self::$whereSpecialStrings)) {
			$this->whereParts[] = $property;
			$this->whereHasLastOperator = true;
			return $this;
		}
		return $this->getProperty($property);
	}

	public function __set($property, $value) {
		return $this->update(array($property => $value));
	}

	/**
	 * @throws Exception
	 * @param  $method
	 * @param  $attributes
	 * @return Collection
	 */
	public function __call($method, $attributes) {
		if(!$attributes) {
			throw new Exception('Unkown method "' . $method . '" requested');
		}
		return $this->filter($method, $attributes[0], count($attributes) > 1 ? $attributes[1] : '=');
	}

	public function __invoke($value) {
		return $this->getObjectById($value);
	}
}

/**
 *
 * @desc This is exception of unkown objects requests
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 *
 */
class ObjectNotFound extends Exception {

}
