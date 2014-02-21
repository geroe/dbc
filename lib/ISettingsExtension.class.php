<?php
/**
 * Created by JetBrains PhpStorm.
 * User: georgroesch
 * Date: 08.10.12
 * Time: 15:46
 */
interface ISettingsExtension {
	public function __construct();
	public function get($key);
}
