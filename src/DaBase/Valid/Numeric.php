<?php namespace DaBase\Valid;

/**
 *
 * @desc String is numeric validation rule
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 *
 */
class Numeric extends Rule {

	protected $min;
	protected $max;
	public static $defaultErrorText = 'require to be numeric';

	public function __construct($min = null, $max = null, $errorText = null) {
		$this->min = $min;
		$this->max = $max;
		$this->setErrorText($errorText);
	}

	protected function validate() {
		if(!is_numeric($this->value) || round($this->value) != $this->value) {
			return false;
		}
		if($this->min !== null && $this->value < $this->min) {
			return false;
		}
		if($this->max !== null && $this->value > $this->max) {
			return false;
		}
		return true;
	}
}
