<?php namespace DaBase\Connection;

abstract class Helper {

	/** @var \DaBase\Connection */
	protected $db;

	abstract public function getValueQuoteChar();

	abstract public function getNameQuoteChar();

	abstract public function getDefaultPort();

	abstract public function sqlInsert($table, array $data);

	abstract public function sqlRandomFunction();

	abstract public function truncateTable($tableName);

	public function __construct(\DaBase\Connection $db) {
		$this->db = $db;
	}

	public function sqlLimitOffset($limit, $offset = 0) {
		$limit = (int)$limit;
		$offset = (int)$offset;
		return 'LIMIT ' . $limit . ($offset ? ' OFFSET ' . $offset : '');
	}


	public function setTimeZone($timezone) {
		$this->db->query('SET time_zone = ?', $timezone);
	}
}
