<?php
/**
 * Created by JetBrains PhpStorm.
 * User: georgroesch
 * Date: 06.09.12
 * Time: 16:31
 */
class ChangeFactory {
	/**
	 * @var DbChangesSettings
	 */
	private $settings;

	/**
	 * @var CacheFactory
	 */
	private $cacheFactory;

	/**
	 * @param DbChangesSettings $settings
	 */
	final public function __construct(DbChangesSettings $settings, CacheFactory $cacheFactory) {
		$this->settings = $settings;
		$this->cacheFactory = $cacheFactory;
	}

	/**
	 * @return ChangeFile
	 * @throws Exception
	 */
	final public function getPersistentBackend() {
		$backend = $this->settings->getChangeBackend();
		if ($backend=='file') {
			return new ChangeFile($this->settings);
		}
		throw new Exception('No suitable backend found for type "'.$backend.'"');
	}

	public function getList() {
		$stack=array();

		$backend = $this->getPersistentBackend();

		foreach ($backend->ls() AS $filename) {
			$cf = new Change($this->settings,$this,$this->cacheFactory->getPersistentBackend());
			$cf->parseFile($filename)->checkIsExecuted();
			$stack[] = $cf;
		}

		return $stack;
	}

	/**
	 * @param string $filename
	 * @return Change
	 */
	public function getChange($filename) {
		$cf = new Change($this->settings,$this,$this->cacheFactory->getPersistentBackend());
		$cf->parseFile($filename)->checkIsExecuted();

		return $cf;
	}

	/**
	 * @return Change
	 */
	public function newChange() {
		$cf = new Change($this->settings,$this,$this->cacheFactory->getPersistentBackend());

		return $cf;
	}

	/**
	 * @return IPersistentCache
	 */
	final public function getCacher() {
		return $this->cacheFactory->getPersistentBackend();
	}
}
