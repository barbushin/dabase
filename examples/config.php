<?php

define('DB_MYSQL', 'mysql');
define('DB_POSTGRES', 'pgsql');

define('DB_TYPE', DB_MYSQL);
define('DB_CONNECTION_CLASS', 'DaBase\Connection\MySQLi');
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'test');
define('DB_PERSISTENT', true);

ini_set('display_errors', true);
error_reporting(E_ALL);

require_once(__DIR__ . '/../src/DaBase/__autoload.php');

spl_autoload_register(function ($class) {
	if(strpos($class, 'DaBase') !== 0) {
		$filePath = __DIR__ . '/' . $class . '.php';
		file_exists($filePath) && require_once($filePath);
	}
});
