<?php namespace DaBase\Connection\Helper;

class MySQL extends \DaBase\Connection\Helper {

	public function getDefaultPort() {
		return 3306;
	}

	public function getValueQuoteChar() {
		return '\'';
	}

	public function getNameQuoteChar() {
		return '`';
	}

	public function sqlInsert($table, array $data) {
		return 'INSERT INTO ' . $this->db->quoteName($table) . ($data ? ' SET ' . $this->db->quoteEquals($data, ', ') : ' VALUES()');
	}

	public function sqlRandomFunction() {
		return 'RAND()';
	}

	public function truncateTable($tableName) {
		$this->db->query('TRUNCATE TABLE ' . $this->db->quoteName($tableName));
	}
}
