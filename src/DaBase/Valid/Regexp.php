<?php namespace DaBase\Valid;

/**
 *
 * @desc String validation by RegExp pattern rule
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 *
 */
class Regexp extends Rule {

	protected $regexp;
	public static $defaultErrorText = 'wrong format';

	function __construct($regexp, $errorText = null) {
		$this->regexp = $regexp;
		$this->setErrorText($errorText);
	}

	protected function validate() {
		return preg_match($this->regexp, $this->value);
	}
}

