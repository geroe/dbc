<?php
/**
 * Created by JetBrains PhpStorm.
 * User: georgroesch
 * Date: 06.09.12
 * Time: 16:31
 */
class CacheFactory {
	/**
	 * @var DbChangesSettings
	 */
	private $settings;

	/**
	 * @param DbChangesSettings $settings
	 */
	final public function __construct(DbChangesSettings $settings) {
		$this->settings = $settings;
	}

	/**
	 * @return CacheFile
	 * @throws Exception
	 */
	final public function getPersistentBackend() {
		$backend = $this->settings->getCacheBackend();
		if ($backend=='file') {
			return new CacheFile($this->settings);
		} else if ($backend=='db') {
			return new CacheDb($this->settings);
		}
		throw new Exception('No suitable caching backend found for type "'.$backend.'"');
	}
}
