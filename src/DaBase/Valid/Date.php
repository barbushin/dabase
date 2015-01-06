<?php namespace DaBase\Valid;

/**
 *
 * @desc String is numeric validation rule
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 *
 */
class Date extends Rule {

	protected $min;
	protected $max;
	public static $defaultErrorText = 'wrong date format';

	public function __construct($errorText = null) {
		$this->setErrorText($errorText);
	}

	protected function validate() {
		return preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $this->value, $m) && checkdate($m[2], $m[3], $m[1]);
	}
}
