<?php
/**
 * Created by JetBrains PhpStorm.
 * User: georgroesch
 * Date: 10.09.12
 * Time: 12:02
 */
class ActionDoExecuteSql extends AAction {
	public function process(&$return,$req) {
		$cf = new Change($this->settings,$this->changeFactory,$this->changeFactory->getCacher());
		$cf->parseFile(DBC_EXECUTION_MODE=='web' ? $req['file'] : $req[0]);

		$return['data'] = $cf->doExecuteSql();
	}
}
