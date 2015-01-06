<?php namespace DaBase\Valid;

/**
 *
 * @desc String length validation rule
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 *
 */
class Length extends Rule {

	public static $defaultErrorText = 'the length require to be from %minLength% to %maxLength% symbols';

	protected $minLength;
	protected $maxLength;

	function __construct($minLength = null, $maxLength = null, $errorText = null) {
		$this->minLength = $minLength;
		$this->maxLength = $maxLength;
		$this->setErrorText($errorText);
	}

	protected function validate() {
		return (!$this->minLength || strlen($this->value) >= $this->minLength) && (!$this->maxLength || strlen($this->value) <= $this->maxLength) ? true : false;
	}

	public function getErrorText() {
		$errorText = parent::getErrorText();
		$errorText = str_replace('%minLength%', $this->minLength, $errorText);
		$errorText = str_replace('%maxLength%', $this->maxLength, $errorText);
		return $errorText;
	}
}

