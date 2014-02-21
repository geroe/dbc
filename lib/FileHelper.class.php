<?php
/**
 * Created by JetBrains PhpStorm.
 * User: georgroesch
 * Date: 04.09.12
 * Time: 13:58
 */
class FileHelper {
	/**
	 * @var DbChangesSettings
	 */
	private $settings;

	public function __construct(DbChangesSettings $settings) {
		$this->settings = $settings;
	}

	/**
	 * @param string $dir
	 * @param string $filename
	 * @return string
	 * @throws Exception
	 */
	protected function load($dir,$filename) {
		if (!file_exists($dir.$filename)) {
			throw new Exception('File "'.$dir.$filename.'" does not exist.');
		}

		if (!is_readable($dir.$filename)) {
			throw new Exception('File "'.$dir.$filename.'" is not readable.');
		}

		$contents = file_get_contents($dir.$filename);

		if ($contents===false) {
			throw new Exception('An unknown error occured while reading file "'.$dir.$filename.'".');
		}

		return $contents;
	}

	/**
	 * @param string $dir
	 * @param string $filename
	 * @param string $contents
	 * @param bool $append
	 * @throws Exception
	 */
	protected function save($dir,$filename,$contents,$append=false) {
		if (file_exists($dir.$filename) && !is_writable($dir.$filename)) {
			throw new Exception('File "'.$dir.$filename.'" is not writable.');
		} else if (!is_writable($dir)) {
			throw new Exception('Directory "'.$dir.'" is not writable.');
		}

		$fh = fopen($dir.$filename,($append ? 'a' : 'w'));
		fputs($fh,$contents,strlen($contents));
		fclose($fh);
	}

	/**
	 * @param string $dir
	 * @param string $filename
	 * @throws Exception
	 */
	protected function delete($dir,$filename) {
		if (!file_exists($dir.$filename) || !is_writable($dir.$filename)) {
			throw new Exception('File "'.$dir.$filename.'" is not writable.');
		}

		if (!unlink($dir.$filename)) {
			throw new Exception('An unknown error occured while deleting file "'.$dir.$file.'"');
		}
	}

	/**
	 * Gets the directory relative to DOC_ROOT
	 * @param string $dirname
	 * @return string
	 * @throws Exception
	 */
	protected function getDir($dirname='') {
		$dir = realpath($this->settings->getDocRoot().(!empty($dirname) ? $dirname : ''));
		if (substr($dir,0,-1)!='/') { $dir.='/'; }
		if (!$dir) {
			throw new Exception('Directory "'.$dir.'" not found.');
		}
		return $dir;
	}

	/**
	 * @param string $dir
	 * @param string $filename
	 * @return bool
	 */
	protected function fileExists($dir,$filename) {
		return file_exists($dir.$filename);
	}

	protected function getTime($dir,$name) {
		if (!$this->fileExists($dir,$name)) {
			throw new Exception('File "'.$dir.$name.'" does not exist.');
		}

		return date('Y-m-d H:i:s',filemtime($dir.$name));
	}

	protected function getOwner($dir,$name) {
		if (!function_exists('posix_getpwuid')) {
			return "unknown_posix_user";
		}

		if (!$this->fileExists($dir,$name)) {
			throw new Exception('File "'.$dir.$name.'" does not exist.');
		}

		$userdata = posix_getpwuid(fileowner($dir.$name));
		return $userdata['name'];
	}
}