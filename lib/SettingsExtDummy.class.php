<?php
/**
 * Created by JetBrains PhpStorm.
 * User: georgroesch
 * Date: 08.10.12
 * Time: 15:47
 */
class SettingsExtDummy implements ISettingsExtension {
	/**
	 * @var Config
	 */
	private $config;

	public function __construct() {
		//unneccessary constructor
		//here you could instantiate a config object
		$this->config = new stdClass();

		$this->config->hostname = 'localhost';
		$this->config->dbname = 'mydb';
		$this->config->username = 'dummy';
		$this->config->password = 'dummy';
	}

	/**
	 * @param $key
	 * @return mixed
	 */
	public function get($key) {
		return $this->config->__get($key);
	}
}
