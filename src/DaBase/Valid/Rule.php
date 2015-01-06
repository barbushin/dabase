<?php namespace DaBase\Valid;

/**
 *
 * @desc Abstract validation rule
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 *
 */
abstract class Rule {

	public static $defaultErrorText = 'not valid';
	protected $errorText;
	protected $value;

	public function isValid($value) {
		$this->value = $value;
		return $this->isEmpty() || $this->validate();
	}

	protected function isEmpty() {
		return $this->value == '';
	}

	abstract protected function validate();

	public function getErrorText() {
		return str_replace('%value%', is_scalar($this->value) ? $this->value : print_r($this->value, true), $this->errorText ? $this->errorText : static::$defaultErrorText);
	}

	public function setErrorText($errorText) {
		if($errorText) {
			$this->errorText = $errorText;
		}
	}
}
