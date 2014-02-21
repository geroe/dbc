<?php
/**
 * Created by JetBrains PhpStorm.
 * User: georgroesch
 * Date: 10.09.12
 * Time: 12:02
 */
class ActionSearchByIssue extends ActionListAll {
	protected function filterResults($stack) {
		$out=array();
		$lookfor = (DBC_EXECUTION_MODE=='web' ? $this->request['query'] : $this->request[0]);
		foreach ($stack AS $entry) {
			if (preg_match('/'.$lookfor.'/i',$entry['file'])) {
				$out[]=$entry;
			}
		}

		return $out;
	}
}
