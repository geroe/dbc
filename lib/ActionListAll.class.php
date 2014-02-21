<?php
/**
 * Created by JetBrains PhpStorm.
 * User: georgroesch
 * Date: 10.09.12
 * Time: 12:02
 */
class ActionListAll extends AAction {
	public function process(&$return,$req) {
		$this->request = $req;

		$stack = $this->changeFactory->getList();
		$out = array();
		foreach ($stack AS $entry) {
			$out[]=$entry->toArray(false);
		}

		//override method to reuse general loading mechanism
		$out = $this->filterResults($out);

		//now sort by since -- this is VERY important!
		usort($out,function($a,$b) {
			$a = (int)strtotime($a['since']);
			$b = (int)strtotime($b['since']);

			return $b-$a;
		});

		$return['entries'] = sizeof($out);
		$return['data'] = $out;
		unset($stack);
	}

	protected function filterResults($stack) {
		return $stack;
	}
}
