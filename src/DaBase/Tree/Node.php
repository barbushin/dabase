<?php namespace DaBase\Tree;

/**
 * @see https://github.com/barbushin/dabase
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 *
 */
class Node extends \DaBase\Object {

	public $leftId;
	public $rightId;
	public $parentId;
	public $level;

	/** @var Node[] */
	public $_childNodes;

	public function isLeaf() {
		return $this->rightId - $this->leftId == 1;
	}
}
