<?php
/**
 * Created by JetBrains PhpStorm.
 * User: georgroesch
 * Date: 11.09.12
 * Time: 12:54
 */
interface IPersistentCache {
	public function store($name,$content,$append=false);
	public function get($name);
	public function remove($name);
	public function exists($name);
	public function who($name);
	public function when($name);
}
