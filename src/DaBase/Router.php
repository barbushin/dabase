<?php namespace DaBase;

/**
 *
 * @desc This class provides routs of collections classes and tables
 * @see https://github.com/barbushin/dabase
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 *
 */
class Router {

	public $baseCollectionsClass = 'DaBase\Collection';
	public $baseObjectsClass = 'DaBase\Object';
	public $ruleCollectionClass = 'ucAllWords|Collection'; // e.x. ucAllWords
	public $ruleObjectsClass = 'ucAllWords|Object'; // e.x. manyToOne|ucAllWords
	public $ruleTableName = 'lcAllWords';
	public $ruleJoinFieldName = 'manyToOne|ucNotFirstWords|Id';
	/** @var Collection[] */
	public $cachedInits = array();
	public $cachedCollection = array();

	protected static function classExists($class) {
		try {
			return class_exists($class);
		}
		catch(Exception $e) {
		}
	}

	/**
	 * @param $alias
	 * @param Connection $db
	 * @return Collection
	 */
	public function getCollectionByAlias($alias, Connection $db) {
		$inits = $this->getCachedInits($alias);
		if($inits) {
			list($collectionClass, $objectsClass, $table) = $inits;
		}
		else {
			$collectionClass = $this->getCollectionClass($alias);
			$objectsClass = $this->getObjectsClass($alias, $collectionClass);
			$table = $this->getTableName($alias, $collectionClass);
			$this->setCachedInits($alias, array($collectionClass, $objectsClass, $table));
		}
		return new $collectionClass($db, $alias, $table, $objectsClass);
	}

	/**************************************************************
	 * ROUTS CACHE
	 **************************************************************/

	protected function setCachedInits($alias, $inits) {
		$this->cachedCollection[$alias] = $inits;
	}

	protected function getCachedInits($alias) {
		return isset($this->cachedInits[$alias]) ? $this->cachedInits[$alias] : null;
	}

	/**************************************************************
	 * ROUTS METHODS (GETTER -> OBJECT -> TABLE)
	 **************************************************************/

	protected function getCollectionClass($alias) {
		if($this->ruleCollectionClass) {
			$collectionClass = self::fetchNameByRule($alias, $this->ruleCollectionClass);
			if(self::classExists($collectionClass)) {
				return $collectionClass;
			}
		}
		return $this->baseCollectionsClass;
	}

	protected function getObjectsClass($alias, $collectionClass) {
		if($collectionClass::objectsClass) {
			return $collectionClass::objectsClass;
		}
		if($this->ruleObjectsClass) {
			$objectsClass = self::fetchNameByRule($alias, $this->ruleObjectsClass);
			if(self::classExists($objectsClass)) {
				return $objectsClass;
			}
		}
		return $this->baseObjectsClass;
	}

	protected function getTableName($alias, $collectionClass) {
		return $collectionClass::table ? $collectionClass::table : self::fetchNameByRule($alias, $this->ruleTableName);
	}

	public function getJoinFieldNameByCollection(Collection $collection) {
		return self::fetchNameByRule($collection->getAlias(), $this->ruleJoinFieldName);
	}

	/**************************************************************
	 * FETCHING NAMES BY RULES
	 **************************************************************/

	public static function fetchNameByRule($name, $rulesString) {
		$prefix = '';
		$postfix = '';
		foreach(explode('|', $rulesString) as $i => $rule) {
			switch($rule) {
				case 'manyToOne': // usersComments -> userComment OR users_comments -> user_comment
					$name = preg_replace('/(e?s)([_A-Z]|$)/', '\\2', $name);
					break;
				case 'oneToMany': // userComment -> usersComments OR user_comment -> users_comments
					$name = preg_replace('/ss([_A-Z]|$)/', 'ses\\1', preg_replace('/([A-Z_]|$)/', 's\\1', $name));
					break;
				case 'ucAllWords': // userComment -> UserComment OR user_comment -> UserComment
					$name = preg_replace_callback('/(^|_)(.)/', function ($matches) {
						return strtoupper($matches[2]);
					}, $name);
					break;
				case 'ucNotFirstWords': // UserComment -> userComment OR user_comment -> userComment
					$name = preg_replace_callback('/_(.)/', function ($matches) {
						return strtoupper($matches[1]);
					}, $name);
					break;
				case 'lcAllWords': // UserComment -> user_comment OR userComment -> user_comment
					$name = strtolower(preg_replace('/(.)([A-Z])/', '\\1_\\2', $name));
					break;
				default:
					if($i) { // 'ucAllWords|Collection' : usersComments > UsersCommentsCollection
						$postfix = $rule;
					}
					else { // 'Collection|ucAllWords' : usersComments > CollectionUsersComments
						$prefix = $rule;
					}
			}
		}
		return $prefix . $name . $postfix;
	}
}
