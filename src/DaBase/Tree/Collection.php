<?php namespace DaBase\Tree;

/**
 * @see https://github.com/barbushin/dabase
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 *
 */
class Collection extends \DaBase\Collection {

	const rootId = 1;
	const objectsClass = 'DaBase\Tree\Node';

	public function getRootId() {
		return self::rootId;
	}

	protected function fixQueryNameQuotes($sql) {
		return str_replace('`', $this->db->getHelper()->getNameQuoteChar(), $sql);
	}

	/**
	 * Proxy method required to fix PostgreSQL problem with lowercasing not quoted columns names
	 * @param $prepareSql
	 * @return mixed
	 */
	protected function query($prepareSql) {
		$args = func_get_args();
		$args[0] = $this->fixQueryNameQuotes($args[0]);
		return call_user_func_array(array($this->db, 'query'), $args);
	}

	public function addRootNode(Node $node) {
		$this->db->begin();
		$this->db->getHelper()->truncateTable($this->table);
		$node->leftId = 1;
		$node->rightId = 2;
		$node->level = 0;
		parent::insertObject($node, false);
		$this->db->commit();
		return $node;
	}

	public function addNode(Node $node, $parentId = null, $checkId = true, $skipValidation = false) {
		$parentNode = $this->getObjectById($parentId !== null ? $parentId : $node->parentId);

		$this->db->begin();
		$this->query('UPDATE @ SET `leftId` = `leftId` + 2, `rightId` = `rightId` + 2 WHERE `leftId` > ?', $this->table, $parentNode->rightId);
		$this->query('UPDATE @ SET `rightId` = `rightId` + 2 WHERE `rightId` >= ? AND `leftId` < ?', $this->table, $parentNode->rightId, $parentNode->rightId);

		$node->parentId = $parentNode->id;
		$node->leftId = $parentNode->rightId;
		$node->rightId = $parentNode->rightId + 1;
		$node->level = $parentNode->level + 1;
		parent::insertObject($node, $checkId, $skipValidation);

		$this->db->commit();
		return $node;
	}

	public function isTreeValid() {
		if(!$this->getByQuery($this->fixQueryNameQuotes('SELECT * FROM @ WHERE `leftId` >= `rightId`'), $this->table)) {
			return false;
		}
	}

	public function deleteNode($nodeId) {
		$this->db->begin();
		$node = $this->getObjectById($nodeId);

		$this->query('DELETE FROM @ WHERE `leftId` >= ? AND `rightId` <= ?', $this->table, $node->leftId, $node->rightId);

		$delDiff = $node->rightId - $node->leftId + 1;
		$this->query('UPDATE @ SET `leftId` = `leftId` - ?, `rightId` = `rightId` - ? WHERE `leftId` > ?', $this->table, $delDiff, $delDiff, $node->rightId);
		$this->query('UPDATE @ SET `rightId` = `rightId` - ? WHERE `rightId` > ? AND `leftId` < ?', $this->table, $delDiff, $node->rightId, $node->leftId);

		$this->db->commit();
		return $this;
	}

	public function filterPopUp($nodeId, $withSelf = false) {
		$node = $this->getObjectById($nodeId);
		return $this->leftId($node->leftId, $withSelf ? '<=' : '<')->rightId($node->rightId, $withSelf ? '>=' : '>');
	}

	public function filterPopDown($nodeId, $withSelf = false) {
		$node = $this->getObjectById($nodeId);
		return $this->leftId($node->leftId, $withSelf ? '>=' : '>')->leftId($node->rightId, '<');
	}

	public function getNodes($parentId = null, $limitLevel = null) {
		return $this->filterChildNodes($parentId, false, $limitLevel)->get();
	}

	public function getTree($parentId = null, $limitLevel = null) {
		return $this->convertNodesArrayToTree($this->filterChildNodes($parentId, true, $limitLevel)->get());
	}

	public function getSubTree($parentId = null, $limitLevel = null) {
		return $this->convertNodesArrayToTree($this->filterChildNodes($parentId, false, $limitLevel)->get());
	}

	public function filterChildNodes($parentId = null, $withParent = false, $limitLevel = null) {
		$limitLevel = (int)$limitLevel;

		if(!$parentId) {
			$parentId = $this->getRootId();
		}

		if($limitLevel == 1) {
			$this->parentId($parentId);
		}
		else {
			$node = $this->getObjectById($parentId);
			$this->leftId($node->leftId, $withParent ? '>=' : '>');
			$this->rightId($node->rightId, $withParent ? '<=' : '<');
			if($limitLevel) {
				$this->level($node->level + $limitLevel, '<');
			}
		}
		return $this;
	}

	// TODO: fix to allow using UNSIGNED `leftId` and rightId
	public function moveNode($nodeId, $newParentId) {
		$this->db->begin();
		$node = $this->getObjectById($nodeId);
		$newParentNode = $this->getObjectById($newParentId);

		$diffId = $node->rightId - $node->leftId + 1;

		$this->query('UPDATE @ SET `leftId` = 0-(`leftId`), `rightId` = 0-(`rightId`) WHERE `leftId` >= ? AND `rightId` <= ?', $this->table, $node->leftId, $node->rightId);
		$this->query('UPDATE @ SET `leftId` = `leftId` - ? WHERE `leftId` > ?', $this->table, $diffId, $node->rightId);
		$this->query('UPDATE @ SET `rightId` = `rightId` - ? WHERE `rightId` > ?', $this->table, $diffId, $node->rightId);
		$this->query('UPDATE @ SET `leftId` = `leftId` + ? WHERE `leftId` >= ?', $this->table, $diffId, $newParentNode->rightId > $node->rightId ? $newParentNode->rightId - $diffId : $newParentNode->rightId);
		$this->query('UPDATE @ SET `rightId` = `rightId` + ? WHERE `rightId` >= ?', $this->table, $diffId, $newParentNode->rightId > $node->rightId ? $newParentNode->rightId - $diffId : $newParentNode->rightId);
		$this->query('UPDATE @ SET `leftId` = 0-(`leftId`) + ?, `rightId` = 0-(`rightId`) + ?, level = level + ' . ($newParentNode->level - $node->level + 1) . ' WHERE `leftId` <= ? AND `rightId` >= ?', $this->table, $newParentNode->rightId > $node->rightId ? $newParentNode->rightId - $node->rightId - 1 : $newParentNode->rightId - $node->rightId - 1 + $diffId, $newParentNode->rightId > $node->rightId ? $newParentNode->rightId - $node->rightId - 1 : $newParentNode->rightId - $node->rightId - 1 + $diffId, 0 - $node->leftId, 0 - $node->rightId);
		$this->query('UPDATE @ SET `parentId` = ? WHERE id=?', $this->table, $newParentNode->id, $node->id);

		$this->db->commit();
		return $this;
	}

	/**
	 * @param array $nodes
	 * @return Node[]
	 */
	protected function convertNodesArrayToTree(array $nodes) {
		$nodesTree = array();

		if($nodes) {
			$topLevel = 999999;
			$topLevelNodes = array();
			$childNodes = array();
			foreach($nodes as $node) {
				if($node->level < $topLevel) {
					$topLevel = $node->level;
					$topLevelNodes = array();
				}
				if($node->level == $topLevel) {
					$topLevelNodes[$node->id] = $node;
				}
				$childNodes[$node->parentId][$node->id] = $node;
			}

			$nodesTree = $topLevelNodes;
			$parents = $topLevelNodes;
			while($parents) {
				foreach($parents as $i => $parent) {
					if(isset($childNodes[$parent->id])) {
						$parent->_childNodes = $childNodes[$parent->id];
						foreach($childNodes[$parent->id] as $child) {
							$parents[$child->id] = $child;
						}
					}
					else {
						$parent->_childNodes = array();
					}
					unset($parents[$i]);
				}
			}
		}

		return $nodesTree;
	}

	/**************************************************************
	 * CRUD METHODS OVERRIDE
	 **************************************************************/

	public function insertObject(\DaBase\Object $object, $checkId = true, $skipValidation = false) {
		return $this->addNode($object, null, $checkId, $skipValidation);
	}

	public function deleteObject(\DaBase\Object $object) {
		return $this->deleteNode($object->id);
	}

	protected function deleteByFilter() {
		foreach($this->orderBy('leftId')->getColumn('id') as $nodeId) {
			$this->deleteNode($nodeId);
		}
		$this->resetFilter();
		return $this;
	}
}
