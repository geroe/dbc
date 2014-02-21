<?php
/**
 * Created by JetBrains PhpStorm.
 * User: georgroesch
 * Date: 06.09.12
 * Time: 11:55
 */
interface IPersistentFile
{
	public function load($location,$name);
	public function save($location,$name,$content,$append=false);
	public function delete($location,$name);
}
