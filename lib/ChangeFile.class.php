<?php
/**
 * Created by JetBrains PhpStorm.
 * User: georgroesch
 * Date: 04.09.12
 * Time: 13:58
 */
class ChangeFile extends FileHelper implements IPersistentChange {
	/**
	 * Location of the ChangeFiles folder relative to DOC_ROOT
	 */
	const FILE_ROOT = 'changes/';

	/**
	 * @param $filename
	 * @return string
	 */
	public function get($filename) {
		return parent::load($this->getDir(self::FILE_ROOT),$filename);
	}

	/**
	 * @param ChangeFile $cf
	 * @param bool $overwrite
	 * @throws Exception
	 */
	public function store(Change $cf,$overwrite=true) {
		if ($cf->getIssueNumber()=='') {
			throw new Exception('Issue# is empty.');
		}

		if ((int)$cf->getIssueCount()==0) {
			throw new Exception('Issue Count is empty.');
		}

		$filename = $cf->generateFilename($cf->getIssueNumber(),$cf->getIssueCount());

		if (!$overwrite) {
			if (parent::fileExists($this->getDir(self::FILE_ROOT),$filename)) {
				throw new Exception('Change already exists.');
			}
		}

		parent::save($this->getDir(self::FILE_ROOT),$filename,$cf->stringify());
	}

	/**
	 * @return array
	 */
	public function ls() {
		$dir = dir($this->getDir(self::FILE_ROOT));
		$list=array();
		while ($entry = $dir->read()) {
			try {
				if (!preg_match('/^([a-z0-9]+)_(\d+)\.sql$/',$entry)) {
					throw new Exception('File name "'.$entry.'" could not be parsed.');
				}
				$list[]=$entry;
			} catch (Exception $e) {
				//this was not a proper named file -- ignore
			}
		}
		return $list;
	}
}