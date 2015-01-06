<?php namespace DaBase;

/**
 *
 * @desc This class defines Validator with set of rules
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 *
 */
class Validator {

	protected $rules = array();
	protected $errors = array();
	protected $multiErrorMode;
	protected $multiErrorModeImplode = "\n";

	public function __construct($multiErrorMode = false) {
		$this->multiErrorMode = $multiErrorMode;
	}

	public function add($valueName, array $rules) {
		if(!isset($this->rules[$valueName])) {
			$this->rules[$valueName] = array();
		}
		foreach($rules as $rule) {
			$this->rules[$valueName][] = $rule;
		}
	}

	public function removeRules($valueName) {
		if(isset($this->rules[$valueName])) {
			$this->rules[$valueName] = array();
		}
	}

	public function getRules($valueName) {
		if(!isset($this->rules[$valueName])) {
			throw new Exception('Unkown value name "' . $valueName . '"');
		}
		return $this->rules[$valueName];
	}

	public function isValid($values, $valuesNames = null, $throwException = true, $excludeValuesNames = null) {
		if(!$valuesNames || $valuesNames === true) {
			$valuesNames = array_keys($this->rules);
		}

		if(!is_array($valuesNames)) {
			$valuesNames = array_map('trim', explode(',', $valuesNames));
		}
		if($excludeValuesNames) {
			if(!is_array($excludeValuesNames)) {
				$excludeValuesNames = array_map('trim', explode(',', $excludeValuesNames));
			}
			$valuesNames = array_diff($valuesNames, $excludeValuesNames);
		}

		$this->errors = array();
		foreach($valuesNames as $valueName) {
			$error = $this->getValueRulesError($valueName, isset($values[$valueName]) ? $values[$valueName] : null);
			if($error) {
				$this->errors[$valueName] = $error;
			}
		}

		if($this->errors && $throwException) {
			throw new Validator_Exception($this->errors);
		}

		return !$this->errors;
	}

	protected function getValueRulesError($valueName, $value) {
		$error = null;

		if(isset($this->rules[$valueName])) {
			foreach($this->rules[$valueName] as $rule) {
				if(!$rule->isValid($value)) {
					if($this->multiErrorMode) {
						$error[] = $rule->getErrorText();
					}
					else {
						$error = $rule->getErrorText();
						break;
					}
				}
			}
		}
		if($error && $this->multiErrorMode && $this->multiErrorModeImplode) {
			return implode($this->multiErrorModeImplode, $error);
		}

		return $error;
	}

	public function getErrors() {
		return $this->errors;
	}
}

/**
 *
 * @desc This is standart exception class for validation errors
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 *
 */
class Validator_Exception extends Exception {

	protected $errors = array();

	public function __construct(array $errors) {
		$this->errors = $errors;
		foreach($errors as $var => $error) {
			$errorsInStrings[] = "$var($error)";
		}
		if($errors) {
			parent::__construct('Validation failed with errors in fields: ' . implode(', ', $errorsInStrings));
		}
	}

	public function getErrors() {
		return $this->errors;
	}
}
