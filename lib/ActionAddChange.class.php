<?php
/**
 * Created by JetBrains PhpStorm.
 * User: georgroesch
 * Date: 10.09.12
 * Time: 12:02
 */
class ActionAddChange extends AAction {
	public function process(&$return,$req) {
		if (DBC_EXECUTION_MODE=='cli') {
			throw new Exception('Not allowed in CLI mode.');
		}

		if (!isset($req['data'])) {
			throw new Exception('No data given.');
		}

		if (!$this->settings->getAllowAdd()) {
			throw new Exception('Add is not allowed.');
		}

		$data = (array)$req['data'];
		$cf = $this->changeFactory->newChange();

		$cf->setIssueNumber($data['issue_number']);
		$cf->setIssueCount($data['issue_count']);
		$cf->setAuthor($data['author']);
		$cf->setSince($data['since']);

		if (!empty($data['db'])) {
			$cf->setDb($data['db']);
		}

		if (!empty($data['message'])) {
			$cf->setMessage($data['message']);
		}

		if (isset($data['slow'])) {
			$cf->setSlow(true);
		}

		if (isset($data['super'])) {
			$cf->setRights('super');
		}

		$cf->setSql($data['sql']);
		if (is_array($data['execute']) ) {
			foreach ($data['execute'] AS $exec) {
				if (trim($exec)) { $cf->setExecute($exec); }
			}
		}

		$cf->save();

		if (isset($data['executed'])) {
			$cf->setIsExecuted();
		}
	}
}
