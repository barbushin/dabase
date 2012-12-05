<?php

class UserObject extends DaBase_Object {
	
	public $login;
	public $password;
	public $posts;
	public $isModerator;
	public $isRoot;
	public $isActive;
	
	protected function initValidator() {
		$validator = new DaBase_Validator();
		
		$validator->add('login', array(
		new DaBase_Valid_Required(), 
		new DaBase_Valid_Length(3, 20), 
		new DaBase_Valid_Regexp('/^[a-z\d]*$/ui')));
		
		$validator->add('password', array(
		new DaBase_Valid_Required(), 
		new DaBase_Valid_Regexp('/^[a-z\d]*$/ui'), 
		new DaBase_Valid_Length(6, 50)));
		
		return $validator;
	}
}