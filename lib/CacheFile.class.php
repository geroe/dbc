<?php
/**
 * Created by JetBrains PhpStorm.
 * User: georgroesch
 * Date: 04.09.12
 * Time: 13:58
 */
class CacheFile extends FileHelper implements IPersistentCache {

	/**
	 * Location of the cache dir
	 */
	const CACHE_ROOT = 'cache/';

	/**
	 * @param string $name
	 * @param mixed $content
	 * @param bool $append
	 * @void
	 */
	public function store($name,$content,$append=false) {
		if (is_array($content)) {
			$content = json_encode($content);
		}
		$this->save($this->getDir(self::CACHE_ROOT),$name,$content,$append);
	}

	/**
	 * @param string $name
	 * @return string
	 */
	public function get($name) {
		$val = $this->load($this->getDir(self::CACHE_ROOT),$name);
		$json = json_decode($val,true);
		return (is_null($json) ? $val : $json);
	}

	/**
	 * @param string $name
	 * @void
	 */
	public function remove($name) {
		$this->delete($this->getDir(self::CACHE_ROOT),$name);
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function exists($name) {
		return $this->fileExists($this->getDir(self::CACHE_ROOT),$name);
	}

	/**
	 * @param $name
	 * @return string
	 */
	public function who($name) {
		return $this->getOwner($this->getDir(self::CACHE_ROOT),$name);
	}

	/**
	 * @param $name
	 * @return string
	 */
	public function when($name) {
		return $this->getTime($this->getDir(self::CACHE_ROOT),$name);
	}
}