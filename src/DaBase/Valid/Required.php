<?php namespace DaBase\Valid;

/**
 *
 * @desc Not empty data validation rule
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 *
 */
class Required extends Rule {

	public static $defaultErrorText = 'require to be not empty';
	protected $zeroMeansEmpty;

	public function __construct($zeroMeansEmpty = true, $errorText = null) {
		$this->zeroMeansEmpty = $zeroMeansEmpty;
		$this->setErrorText($errorText);
	}

	public function isValid($value) {
		parent::isValid($value);
		return $this->validate();
	}

	protected function isEmpty() {
		if($this->zeroMeansEmpty) {
			return parent::isEmpty();
		}
		else {
			return $this->value !== 0 && $this->value !== '0' && parent::isEmpty();
		}
	}

	protected function validate() {
		return !$this->isEmpty();
	}
}
