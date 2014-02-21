<?php
/**
 * Created by JetBrains PhpStorm.
 * User: georgroesch
 * Date: 11.09.12
 * Time: 12:55
 */
interface IPersistentChange {
	public function get($filename);
	public function store(Change $cf,$overwrite);
	public function ls();
}
