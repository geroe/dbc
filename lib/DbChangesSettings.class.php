<?php
/**
 * Created by JetBrains PhpStorm.
 * User: georgroesch
 * Date: 06.09.12
 * Time: 16:23
 */
final class DbChangesSettings {
	const CR = "\r";
	const LF = "\n";
	const CRLF = "\r\n";

	private static $instance=null;

	private $settingsData=array();

	private $extClasses=array();

	/**
	 * Singleton Factory method
	 * @static
	 * @return DbChangesSettings
	 */
	public static function getInstance() {
		if (is_null(self::$instance)) {
			self::$instance = new DbChangesSettings();
		}

		return self::$instance;
	}

	private function __construct() {
		//init
		$this->settingsData=array(
			'DOC_ROOT' => getcwd(),
			'changeBackend' => 'file',
			'cacheBackend' => 'file',
			'apiExecuteAsync' => true,
			'lineEnding' => 'LF',
			'allowAdd' => false
		);
		$this->parseIniFile();
	}

	private function parseIniFile() {
		$settings = array();
		$settingStack = array();
		$open_basedir_restriction=ini_get('open_basedir');
		if (empty($open_basedir_restriction)) {
			$settingStack[] = '/etc/dbc.ini';
			$settingStack[] = '/usr/local/etc/dbc.ini';
			if(function_exists("posix_getpwuid") && function_exists("posix_getuid")) {
				$userData = posix_getpwuid(posix_getuid());
				$settingStack[] = $userData['dir'].'/.dbc.ini';
			}
		}
		$settingStack[] = dirname(__FILE__).'/../dbc.ini';
		$settingStack[] = getcwd().'/dbc.ini';

		foreach ($settingStack AS $settingsFile) {
			if (is_readable($settingsFile)) {
				$settings = array_merge(parse_ini_file($settingsFile,true),$settings);
			}
		}

		//merge with default settings
		$settings = array_merge($this->settingsData,$settings);

		if (empty($settings)) {
			throw new Exception('No settings file found. Aborting.');
		}

		if (!isset($settings['dbConn:standard'])) {
			throw new Exception('Mandatory "dbConn:standard" is missing in settings file. Aborting.');
		}

		$this->settingsData = $this->parseValues($settings);
	}

	/**
	 * recursive call for all setting arrays
	 * @param array $settings
	 * @return array
	 */
	private function parseValues($settings) {
		foreach ($settings AS $key => $value) {
			if (!is_array($value)) {
				$settings[$key] = $this->parseValue($value);
			} else {
				$settings[$key] = $this->parseValues($value);
			}
		}

		return $settings;
	}

	/**
	 * if the string contains a pattern like {@Class:value} it will try to instantiate Class and replace the value
	 * @example {@myApp:hostname} will instantiate SettingsExtMyApp.class.php and then call ->get('hostname')
	 * @param string $val
	 * @return string
	 */
	private function parseValue($val) {
		$matches=array();
		if (preg_match_all('/\{@([^:}]+):([^\}]+)\}/',$val,$matches)) {
			foreach($matches[0] AS $matchNr => $needle) {
				$className = 'SettingsExt'.ucfirst($matches[1][$matchNr]);
				if (class_exists($className)) {
					if (!isset($this->extClasses[$className])) {
						$this->extClasses[$className] = new $className;
						if (!$this->extClasses[$className] instanceof ISettingsExtension) {
							//this is not a valid extension class, abort!
							unset($this->extClasses[$className]);
							continue;
						}
					}
					$val = str_replace($needle,$this->extClasses[$className]->get($matches[2][$matchNr]),$val);
				}
			}
		}

		return $val;
	}

	/**
	 * @return string
	 */
	public function getCacheBackend() {
		return $this->settingsData['cacheBackend'];
	}

	/**
	 * @return string
	 */
	public function getChangeBackend() {
		return $this->settingsData['changeBackend'];
	}

	/**
	 * @return string
	 */
	public function getDocRoot() {
		return $this->settingsData['DOC_ROOT'];
	}

	/**
	 * @return array
	 */
	public function getDbConn($db='general') {
		if (!isset($this->settingsData['dbConn:'.$db])) {
			return array();
		}
		return (array)$this->settingsData['dbConn:'.$db];
	}

	/**
	 * @return bool
	 */
	public function getApiExecuteAsync() {
		return (bool)$this->settingsData['apiExecuteAsync'];
	}

	/**
	 * @return string
	 */
	public function getDefaultAuthor() {
		if (!array_key_exists('defaultAuthor',$this->settingsData)) {
			return '';
		}

		return $this->settingsData['defaultAuthor'];
	}

	/**
	 * @return bool
	 */
	public function getAllowAdd() {
		return $this->settingsData['allowAdd'];
	}


	public function getLineEnding() {
		switch($this->settingsData['lineEnding']){
			case 'CR':
				$out = self::CR;
				break;
			case 'CRLF':
				$out = self::CRLF;
				break;
			default:
				$out = self::LF;
		}

		return $out;
	}
}
