<?php

class RootUsersCollection extends DaBase_Collection {

	const table = 'dabase_users';
	const objectsClass = 'UserObject';
	
	protected function postInit() {
		$this->isRoot(true);
	}
}