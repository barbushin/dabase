<?php namespace DaBase\Connection\Helper;

class PostgreSQL extends \DaBase\Connection\Helper {

	public function getDefaultPort() {
		return 5432;
	}

	public function getValueQuoteChar() {
		return '\'';
	}

	public function getNameQuoteChar() {
		return '"';
	}

	public function sqlInsert($table, array $data) {
		return 'INSERT INTO ' . $this->db->quoteName($table) . ($data
			? ' (' . implode(', ', $this->db->quoteNames(array_keys($data))) . ') VALUES (' . implode(', ', $this->db->quoteArray($data)) . ')'
			: ' VALUES()'
		) . ' RETURNING id';
	}

	public function sqlRandomFunction() {
		return 'RANDOM()';
	}

	public function truncateTable($tableName) {
		$this->db->query('TRUNCATE TABLE ' . $this->db->quoteName($tableName) . ' RESTART IDENTITY');
	}
}
