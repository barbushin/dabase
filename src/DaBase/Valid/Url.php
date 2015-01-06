<?php namespace DaBase\Valid;

/**
 *
 * @desc URL validation rule
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 *
 */
class Url extends Rule {

	public static $defaultErrorText = 'wrong url format';

	public function __construct($errorText = null) {
		$this->setErrorText($errorText);
	}

	protected function validate() {
		return preg_match('~^(?i)\b((?:https?://|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:\'".,<>?«»“”‘’]))$~', $this->value);
	}
}
