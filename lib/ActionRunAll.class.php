<?php
/**
 * Created by JetBrains PhpStorm.
 * User: georgroesch
 * Date: 10.09.12
 * Time: 12:02
 */
class ActionRunAll extends ActionListOpen {
	public function process(&$return,$req) {
		if (DBC_EXECUTION_MODE!='cli') {
			throw new Exception('runAll is only valid in CLI mode.');
		}
		parent::process($return,$req);

		$cardStack = (array)$return['data'];
		$cardStack = array_reverse($cardStack); //execute in reverse order

		$errCount=0;
		$cardCount = sizeof($cardStack);
		foreach($cardStack AS $entry) {
			try {
				$changeFile = $entry['file'];
				$cf = new Change($this->settings,$this->changeFactory,$this->changeFactory->getCacher());
				$cf->parseFile($changeFile);
				try {
					if (!$cf->doExecuteSql()) {
						$errStat = $cf->getAsyncStatus();
						throw new Exception($errStat['message']);
					}
					echo '[DONE] '.$changeFile.PHP_EOL;
				} catch (Exception $e) {
					$errCount++;
					echo '[FAILED] '.$changeFile.' :: '.$e->getMessage().PHP_EOL;
					echo $cf->getSql().PHP_EOL;
					if (in_array('--force',$req) || in_array('-f',$req)) {
						try {
							$cf->failedToExecuted();
							echo '[IGNORE] '.$changeFile.PHP_EOL;
						} catch (Exception $e) {
							echo '[FAILED] '.$changeFile.' IGNORE FAILED :: '.$e->getMessage().PHP_EOL;
						}
					}
				}
			} catch (Execution $e) {
				echo '[FAILED] '.$changeFile.' Unrecoverable error :: '.$e->getMessage().PHP_EOL;
			}

			if (sizeof($cf->getExecute())) {
				echo '[IGNORE] '.$changeFile.' PATCHES: '.implode(', ',$cf->getExecute()).PHP_EOL;
			}
		}
		$return['data']=PHP_EOL.'*** FINISHED '.$cardCount.' CHANGES'.($errCount ? ' WITH '.$errCount.' FAIL(S)' : ' SUCCESSFULLY');
		$return['status']='ok';
	}
}
