<?php

/**
 *
 * @desc This class defines object models of database tables with validation support
 * @see https://github.com/barbushin/dabase
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 *
 */
class DaBase_Object {

	public $id;
	/** @var DaBase_Validator|null */
	protected $__validator;

	public function __construct(array $properties = array()) {
		$this->setByArray($properties);
	}

	public function setByArray(array $properties) {
		foreach($properties as $property => $value) {
			$this->$property = $value;
		}
	}

	public function asArray($notEmpty = false, $notAppended = false) {
		$properties = get_object_vars($this);
		foreach($properties as $name => $value) {
			if(($notEmpty && ($value === null || $value === '')) || ($notAppended && $name[0] == '_')) {
				unset($properties[$name]);
			}
		}
		return $properties;
	}

	public function getNotNullProperties($notAppended = true) {
		$properties = array();
		foreach(get_object_vars($this) as $name => $value) {
			if($value !== null && (!$notAppended || $name[0] != '_')) {
				$properties[$name] = $value;
			}
		}
		return $properties;
	}

	/***************************************************************
	VALIDATION
	 **************************************************************/

	public function validate($properties = null, $throwException = true, $excludeProperties = null) {
		$validator = $this->getValidator();
		return $validator ? $validator->isValid($this->asArray(), $properties, $throwException, $excludeProperties) : true;
	}

	/**
	 * @return DaBase_Validator
	 */
	public function getValidator() {
		if(!$this->__validator) {
			$this->__validator = $this->initValidator();
		}
		return $this->__validator;
	}

	protected function initValidator() {
		return false;
	}

	public function getValidationErrors() {
		return $this->getValidator() ? $this->getValidator()->getErrors() : array();
	}

	/***************************************************************
	MAGICS
	 **************************************************************/

	public function leaveFields(array $fields) {
		$clearFields = array();
		foreach($this->asArray(false, true) as $field => $value) {
			if(!in_array($field, $fields)) {
				$clearFields[] = $field;
			}
		}
		return $this->clearFields($clearFields);
	}

	public function clearFields(array $fields) {
		foreach($fields as $field) {
			unset($this->$field);
		}
		return $this;
	}

	public function __get($property) {
		throw new Exception('Unkown property "' . $property . '"');
	}

	public function __unset($property) {
		$this->$property = null;
	}

	public function __clone() {
		$class = get_class($this);
		$clone = new $class($this->asArray());
		$clone->id = null;
		return $clone;
	}

	public function __toString() {
		return $this->id;
	}
}
