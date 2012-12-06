<?php

/**
 *
 * @desc Callback validation rule
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 *
 */
class DaBase_Valid_Callback extends DaBase_Valid_Rule {

	protected $callback;
	protected $trueMeansValid;

	function __construct($callback, $errorText = null, $trueMeansValid = true) {
		if(!is_callable($callback)) {
			throw new Exception('Argument must be callable');
		}
		$this->callback = $callback;
		$this->trueMeansValid = $trueMeansValid;
		$this->setErrorText($errorText);
	}

	protected function validate() {
		$customErrorText = null;
		$isValid = !(call_user_func_array($this->callback, array($this->value, &$customErrorText)) xor $this->trueMeansValid);
		if($customErrorText) {
			$this->setErrorText($customErrorText);
		}
		return $isValid;
	}
}

