<?php
/**
 * Created by JetBrains PhpStorm.
 * User: georgroesch
 * Date: 10.09.12
 * Time: 12:02
 */
class ActionGetDefaultAuthor extends AAction {
	public function process(&$return,$req) {
		$return['data'] = $this->settings->getDefaultAuthor();
	}
}
