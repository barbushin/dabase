<?php namespace DaBase\Valid;

/**
 *
 * @desc Email validation rule
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 *
 */
class Enum extends Rule {

	public static $defaultErrorText = 'value not in list';

	protected $valuesList;

	public function __construct(array $valuesList, $errorText = null) {
		$this->valuesList = $valuesList;
		$this->setErrorText($errorText);
	}

	protected function validate() {
		return in_array($this->value, $this->valuesList);
	}
}
