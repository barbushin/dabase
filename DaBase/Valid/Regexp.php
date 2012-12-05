<?php

/**
 *
 * @desc String validation by RegExp pattern rule
 * @author Barbushin Sergey http://www.linkedin.com/in/barbushin
 *
 */
class DaBase_Valid_Regexp extends DaBase_Valid_Rule {

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

