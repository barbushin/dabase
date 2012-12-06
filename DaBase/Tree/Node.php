<?php

/**
 * @see https://github.com/barbushin/dabase
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 *
 */
class DaBase_Tree_Node extends DaBase_Object {

	public $leftId;
	public $rightId;
	public $parentId;
	public $level;

	/** @var DaBase_Tree_Node[] */
	public $_childNodes;

	public function isLeaf() {
		return $this->rightId - $this->leftId == 1;
	}
}
