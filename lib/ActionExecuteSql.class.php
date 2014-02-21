<?php
/**
 * Created by JetBrains PhpStorm.
 * User: georgroesch
 * Date: 10.09.12
 * Time: 12:02
 */
class ActionExecuteSql extends AAction {
	public function process(&$return,$req) {
		$cf = new Change($this->settings,$this->changeFactory,$this->changeFactory->getCacher());
		$cf->parseFile(DBC_EXECUTION_MODE=='web' ? $req['file'] : $req[0]);

		//only if the setting api_execute_async is true and execution_mode is web, then execute it async
		$async = $this->settings->getApiExecuteAsync() && DBC_EXECUTION_MODE=='web';

		$return['data'] = $cf->executeSql($async);
	}
}
