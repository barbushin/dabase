<?php

/**
 *
 * @desc This class provides routs of collections classes and tables
 * @see http://code.google.com/p/dabase
 * @author Barbushin Sergey http://www.linkedin.com/in/barbushin
 *
 */
class DaBase_Router {

	public $baseCollectionsClass = 'DaBase_Collection';
	public $baseObjectsClass = 'DaBase_Object';
	public $ruleCollectionClass; // e.x. ucAllWords
	public $ruleObjectsClass; // e.x. manyToOne|ucAllWords
	public $ruleTableName = 'lcAllWords';
	public $ruleJoinFieldName = 'manyToOne|ucNotFirstWords|Id';

	protected static function classExists($class) {
		try {
			return class_exists($class);
		}
		catch(Exception $e) {
		}
	}

	public function getCollectionByAlias($alias, DaBase_Connection $db) {
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
	ROUTS CACHE
	 **************************************************************/

	protected function setCachedInits($alias, $inits) {
		$this->cachedCollection[$alias] = $inits;
	}

	protected function getCachedInits($alias) {
		return isset($this->cachedInits[$alias]) ? $this->cachedInits[$alias] : null;
	}

	/**************************************************************
	ROUTS METHODS (GETTER -> OBJECT -> TABLE)
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

	public function getJoinFieldNameByCollection(DaBase_Collection $collection) {
		return self::fetchNameByRule($collection->getAlias(), $this->ruleJoinFieldName);
	}

	/**************************************************************
	FETCHING NAMES BY RULES
	 **************************************************************/

	public static function fetchNameByRule($name, $rulesString) {
		$prefix = '';
		$postfix = '';
		foreach(explode('|', $rulesString) as $i => $rule) {
			switch($rule) {
				case 'manyToOne':
					$name = preg_replace('/(e?s)([_A-Z]|$)/', '\\2', $name); // usersComments -> userComment OR users_comments -> user_comment
					break;
				case 'oneToMany':
					$name = preg_replace('/ss([_A-Z]|$)/', 'ses\\1', preg_replace('/([A-Z_]|$)/', 's\\1', $name)); // userComment -> usersComments OR user_comment -> users_comments
					break;
				case 'ucAllWords':
					$name = preg_replace('/(^|_)(.)/e', 'strtoupper(\'\\2\')', $name); // userComment -> UserComment OR user_comment -> UserComment
					break;
				case 'ucNotFirstWords':
					$name = preg_replace('/_(.)/e', 'strtoupper(\'\\1\')', $name); // UserComment -> userComment OR user_comment -> userComment
					break;
				case 'lcAllWords':
					$name = strtolower(preg_replace('/(.)([A-Z])/', '\\1_\\2', $name)); // UserComment -> user_comment OR userComment -> user_comment
					break;
				default:
					if($i) {
						$postfix = $rule; // 'ucAllWords|Collection' : usersComments > UsersCommentsCollection
					}
					else {
						$prefix = $rule; // 'Collection|ucAllWords' : usersComments > CollectionUsersComments
					}
			}
		}
		return $prefix . $name . $postfix;
	}
}
