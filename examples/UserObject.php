<?php

class UserObject extends DaBase\Object {
	
	public $login;
	public $password;
	public $posts;
	public $isModerator;
	public $isRoot;
	public $isActive;
	
	protected function initValidator() {
		$validator = new \DaBase\Validator();
		
		$validator->add('login', array(
		new DaBase\Valid\Required(), 
		new DaBase\Valid\Length(3, 20), 
		new DaBase\Valid\Regexp('/^[a-z\d]*$/ui')));
		
		$validator->add('password', array(
		new DaBase\Valid\Required(), 
		new DaBase\Valid\Regexp('/^[a-z\d]*$/ui'), 
		new DaBase\Valid\Length(6, 50)));
		
		return $validator;
	}
}
