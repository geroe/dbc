<?php
/**
 * Created by JetBrains PhpStorm.
 * User: georgroesch
 * Date: 04.09.12
 * Time: 21:30
 */

//be as verbose as possible
error_reporting(E_ALL | E_STRICT);

if (!defined('DBC_EXECUTION_MODE')) {
	define('DBC_EXECUTION_MODE','web');
}

$return = array(
	'status' => 'ok'
);

spl_autoload_register(function ($class) {
	require_once(dirname(__FILE__).'/lib/'.$class.'.class.php');
});

try {
	$settings = DbChangesSettings::getInstance();
	$changeFactory = new ChangeFactory($settings,new CacheFactory($settings));

	if (!isset($_REQUEST['action'])) {
		throw new Exception('No action given.');
	}

	$command = 	$_REQUEST['action'];

	if (isset($_REQUEST['cli_args']) && DBC_EXECUTION_MODE=='cli') {
		$_REQUEST = $_REQUEST['cli_args'];
	}

	$className = 'Action'.ucfirst($command);
	if (!file_exists(dirname(__FILE__).'/lib/'.$className.'.class.php')) {
		throw new Exception('Class "'.$className.'" does not exist.');
	}
	if (!is_readable(dirname(__FILE__).'/lib/'.$className.'.class.php')) {
		throw new Exception('Class "'.$className.'" is not readable.');
	}
	if (class_exists($className)) {
		$action = new $className($settings,$changeFactory);

		if (!$action instanceof AAction) {
			throw new Exception('"'.$className.'" is not a valid action.');
		}

		$action->process($return,$_REQUEST); //$return is a pointer!
	} else {
		throw new Exception('Could not find "'.$className.'".');
	}
} catch (Exception $e) {
	$return['status'] = 'error';
	$return['message'] = $e->getMessage();
}

if (DBC_EXECUTION_MODE=='web') {
	header('Content-type: application/json');
	echo json_encode($return);
}
