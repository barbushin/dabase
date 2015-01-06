<?php namespace DaBase\Valid;

/**
 *
 * @desc Email validation rule
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 *
 */
class Email extends Rule {

	protected $noName;
	public static $defaultErrorText = 'wrong email format';

	public function __construct($noName = true, $errorText = null) {
		$this->noName = $noName;
		$this->setErrorText($errorText);
	}

	protected function validate() {
		if($this->noName || !preg_match('/<.+>/u', $this->value)) {
			return preg_match('/^[\w-+]+(\.[\w-+]+)*@[\w\-+]+(\.[\w]{2,})+$/ui', $this->value);
		}
		else {
			return preg_match('/^(.*<)?[\w-]+(\.[\w-+]+)*@[\w\-+]+(\.[\w]{2,})+>$/ui', $this->value);
		}
	}
}
