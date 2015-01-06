<?php namespace DaBase\Valid;

/**
 *
 * @desc Empty data validation rule
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 *
 */
class Excluded extends Rule {

	public static $defaultErrorText = 'required to be empty';

	protected function validate() {
		return $this->isEmpty();
	}
}

