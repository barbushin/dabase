<?php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_DATABASE', 'test');
define('DB_PERSISTANT', true);


function autoloadByDir($class) {
	foreach (array('../', './') as $dir) {
		$filePath = $dir . str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';
		if (is_file($filePath)) {
			return require_once ($filePath);
		}
	}
}
spl_autoload_register('autoloadByDir');