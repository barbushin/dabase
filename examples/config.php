<?php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_DATABASE', 'test');
define('DB_PERSISTANT', true);

require_once(__DIR__ . '/../src/DaBase/__autoload.php');

spl_autoload_register(function ($class) {
	if(strpos($class, 'DaBase') !== 0) {
		$filePath = __DIR__ . '/' . $class . '.php';
		file_exists($filePath) && require_once($filePath);
	}
});
