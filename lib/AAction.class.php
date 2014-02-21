<?php
/**
 * Created by JetBrains PhpStorm.
 * User: georgroesch
 * Date: 10.09.12
 * Time: 12:02
 */
abstract class AAction {
	/**
	 * @var DbChangesSettings
	 */
	protected $settings;

	/**
	 * @var array
	 */
	protected $request;

	/**
	 * @var ChangeFactory
	 */
	protected $changeFactory;

	/**
	 * @param DbChangesSettings $settings
	 */
	final public function __construct(DbChangesSettings $settings, ChangeFactory $changeFactory) {
		$this->settings = $settings;
		$this->changeFactory = $changeFactory;
	}

	abstract public function process(&$return,$req);
}
