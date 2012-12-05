<?php

/**
 *
 * @desc Empty data validation rule
 * @author Barbushin Sergey http://www.linkedin.com/in/barbushin
 *
 */
class DaBase_Valid_Excluded extends DaBase_Valid_Rule {

	public static $defaultErrorText = 'required to be empty';

	protected function validate() {
		return $this->isEmpty();
	}
}

