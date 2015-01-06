<?php namespace DaBase;

class Cache {

	protected $cache = array();
	protected $cacheTags = array();
	protected $cacheRequests = array();
	protected $limit;

	public function __construct($limit = 500) {
		$this->limit = (int)$limit;
	}

	public function add($key, $data, array $tags = array()) {
		$this->checkLimitAndClear();
		$keyHash = $this->hashKey($key);
		$this->cache[$keyHash] = $data;
		$this->cacheTags[$keyHash] = '"' . implode('"', $tags) . '"';
		$this->cacheRequests[$keyHash] = 0;
	}

	protected function checkLimitAndClear() {
		if($this->limit && count($this->cache) > $this->limit && !mt_rand(0, $this->limit / 10)) {
			arsort($this->cacheRequests);
			$keysHashesToClear = array_slice(array_keys($this->cacheRequests), $this->limit, count($this->cacheRequests) - $this->limit, true);
			foreach($keysHashesToClear as $keyHash) {
				$this->deleteByKeyHash($keyHash);
			}
		}
	}

	public function get($key) {
		$keyHash = $this->hashKey($key);
		if(isset($this->cacheRequests[$keyHash])) {
			$this->cacheRequests[$keyHash]++;
			return $this->cache[$keyHash];
		}
		return false;
	}

	public function delete($key) {
		$this->deleteByKeyHash($this->hashKey($key));
	}

	protected function deleteByKeyHash($keyHash) {
		if(isset($this->cache[$keyHash])) {
			unset($this->cache[$keyHash]);
			unset($this->cacheTags[$keyHash]);
			unset($this->cacheRequests[$keyHash]);
		}
	}

	public function clearByTag($tag) {
		$search = '"' . $tag . '"';
		foreach($this->cacheTags as $keyHash => $tags) {
			if(strstr($tags, $search)) {
				$this->deleteByKeyHash($keyHash);
			}
		}
	}

	protected function hashKey($key) {
		return crc32($key);
	}
}
