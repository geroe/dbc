<?php
/**
 * Created by JetBrains PhpStorm.
 * User: georgroesch
 * Date: 10.09.12
 * Time: 12:02
 */
class ActionListDone extends ActionListAll {
	protected function filterResults($stack) {
		return array_filter($stack,function ($entry) {
			return $entry['isExecuted'];
		});
	}
}
