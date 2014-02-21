<?php
/**
 * Created by JetBrains PhpStorm.
 * User: georgroesch
 * Date: 04.09.12
 * Time: 13:54
 */
class Change {
	/**
	 * minimum version this class can parse
	 */
	const MIN_VERSION = 1;

	/**
	 * maximum version this class can parse
	 */
	const MAX_VERSION = 1;

	/**
	 * @var DbChangesSettings
	 */
	private $settings;

	/**
	 * @var ChangeFactory
	 */
	private $changeFactory;

	/**
	 * @var IPersistentCache
	 */
	protected $cacher;

	/**
	 * @var string The issue number, e.g. zen1234
	 */
	protected $issueNumber;

	/**
	 * @var int The number of the change for this card
	 */
	protected $issueCount=1;


	/**
	 * @var int The version of this entry. Has to be MIN_VERSION <= version <= MAX_VERSION
	 * @see Change::MIN_VERSION and Change::MAX_VERSION
	 */
	protected $version=1;

	/**
	 * @var string Timestamp as Y-m-d H:i:s
	 */
	protected $since;

	/**
	 * @var string The initials/shortname of the creator
	 */
	protected $author;

	/**
	 * @var array An array of patches relative to DOC_ROOT
	 * @example path/to/my/patch.php?foo=bar
	 */
	protected $execute=array();

	/**
	 * @var string A message to the world
	 */
	protected $message;

	/**
	 * @var bool Indicates if this will run for a long time (>1min), do NOT execute during prime time
	 */
	protected $slow=false;

	/**
	 * @var string Either not present or [normal|super]; you need super if you want to change triggers, etc.
	 */
	protected $rights='normal';

	/**
	 * @var string Name of the DB Connection in your settings file; default: standard
	 */
	protected $db='standard';

	/**
	 * @var string The SQL part of the change
	 */
	protected $sql;

	/**
	 * @var mixed Was this already executed [true|false|"unknown"]
	 */
	protected $isExecuted='unknown';

	/**
	 * The Constructor
	 * Initialises the since date
	 */
	public function __construct(DbChangesSettings $settings, ChangeFactory $changeFactory, IPersistentCache $cacher) {
		$this->settings=$settings;
		$this->changeFactory=$changeFactory;
		$this->since=date('Y-m-d H:i:s');

		$this->cacher = $cacher;
	}

	/**
	 * Load an issue by object vars issueNumber and issueCount
	 * @return Change $this
	 */
	public function parseIssue() {
		return $this->parseFile($this->generateFilename($this->issueNumber,$this->issueCount),false);
	}

	/**
	 * Load an issue by Filename
	 * @param string $filename - The name of the file to load
	 * @param bool $parseFileName - Should issueNumber and issueCount be set by filename?
	 * @return Change $this
	 * @throws Exception
	 */
	public function parseFile($filename,$parseFileName=true) {
		$contents = $this->changeFactory->getPersistentBackend()->get($filename);
		$contents = explode("\n",$contents);
		$sql = array();
		foreach ($contents AS $line) {
			if (substr($line,0,2)=='#@') { $this->parseDocComment($line); }
			else if (substr($line,0,1)=='#') { continue; }
			else { $sql[] = $line; }
		}

		$this->version = (int)$this->version;
		if ($this->version < self::MIN_VERSION || $this->version > self::MAX_VERSION) {
			throw new Exception(
				'Given version "'.$this->version.'" is not in ['
				.self::MIN_VERSION.'..'.self::MAX_VERSION.']'
			);
		}

		$this->sql = implode("\n",$sql);
		unset($sql);

		if ($parseFileName) {
			$issueData = $this->parseFileName($filename);
			$this->issueNumber=$issueData['issueNumber'];
			$this->issueCount=$issueData['issueCount'];
		}

		return $this;
	}

	/**
	 * Parse a block that starts with #@
	 * @param string $line - The line in the file
	 * @throws Exception
	 */
	protected function parseDocComment($line) {
		$matches = array();
		preg_match('/^#@([a-z]+).*$/',$line,$matches);

		if (!isset($matches[1])) {
			throw new Exception('Error while parsing the DocComments of "'.$line.'"');
		}
		$action = $matches[1];
		//unset($matches);

		switch ($action) {
			case 'slow':
				$this->slow=true;
				break;
			case 'execute':
					$this->execute[] = $this->parseSettingFromDocComment($action,$line);
				break;
			case 'version':
			case 'since':
			case 'db':
			case 'author':
			case 'message':
			case 'rights':
				$this->$action = $this->parseSettingFromDocComment($action,$line);
				break;
			default:
				throw new Exception('Unknown DocComment "'.$action.'" in line "'.$line.'"');
		}
	}

	/**
	 * @param string $issueNumber
	 * @param int $issueCount
	 * @return string
	 */
	public function generateFilename($issueNumber,$issueCount=1) {
		return $issueNumber.'_'.$issueCount.'.sql';
	}

	/**
	 * @param string $filename
	 * @return array
	 * @throws Exception
	 */
	public function parseFileName($filename) {
		$matches = array();
		if (!preg_match('/^([a-z0-9]+)_(\d+)\.sql$/',$filename,$matches)) {
			throw new Exception('File name "'.$filename.'" could not be parsed.');
		}

		return array(
			'issueNumber' => $matches[1],
			'issueCount' => (int)$matches[2]
		);
	}

	/**
	 * Get the setting for an entry like this: #@version 1
	 * @param string $action
	 * @param string $line
	 * @return string - The parsed setting
	 * @throws Exception
	 */
	private function parseSettingFromDocComment($action,$line) {
		$return = trim(substr($line,strlen('#@'.$action)));
		if (empty($return)) {
			throw new Exception('No value found for action "'.$action.'" in line "'.$line.'"');
		}
		return $return;
	}

	/**
	 * Converts the object to a string which can be stored as a file
	 * @return string
	 * @throws Exception
	 */
	public function stringify() {
		$out=array();

		if (empty($this->author)) { throw new Exception('Author must not be empty.'); }

		if (empty($this->execute) && empty($this->sql)) {
			throw new Exception('There is neither an execute command nor a SQL part. One MUST be set.');
		}

		foreach (array_keys(get_class_vars('Change')) AS $var) {
			switch ($var) {
				case 'slow':
					if ($this->slow) { $out[] = $this->getDocComment($var); }
					break;
				case 'execute':
					foreach ($this->execute AS $exec) {
						$out[] = $this->getDocComment($var,$exec);
					}
					unset($exec);
					break;
				case 'rights':
					if ($this->rights=='normal') { break; }
				case 'db':
					if (empty($this->$var) || $this->$var=='standard') { break; }
					$out[] = $this->getDocComment($var,$this->$var);
					break;
				case 'version':
				case 'since':
				case 'author':
				case 'message':
					if (empty($this->$var)) { break; }
					$out[] = $this->getDocComment($var,$this->$var);
					break;
			}
		}

		if (!empty($this->sql)) {
			$out[] = str_replace(array(DbChangesSettings::CRLF,DbChangesSettings::CR),DbChangesSettings::LF,$this->sql);
		}
		$out[] = '';
		$out=implode(DbChangesSettings::LF,$out);

		$lineEnding = $this->settings->getLineEnding();
		if ($lineEnding!=DbChangesSettings::LF) {
			$out=str_replace(DbChangesSettings::LF,$lineEnding,$out);
		}

		return $out;
	}

	/**
	 * @param bool $detailed
	 * @return array
	 */
	public function toArray($detailed=true) {
		$out=array(
			'file' => $this->generateFilename(
				$this->getIssueNumber(),
				$this->getIssueCount()
			),
			'since' => $this->since,
			'author' => $this->author,
			'slow' => $this->slow,
			'super' => $this->rights=='super',
			'db' => $this->db,
			'message' => $this->message,
			'execute' => $this->execute,
			'sql' => trim($this->sql),
			'isExecuted' => $this->isExecuted===true,
			'executable' => $this->isExecutable()
		);

		if (!$detailed) {
			unset($out['sql']);
			$out['execute'] = (sizeof($out['execute'])>0);
		}

		return $out;
	}

	/**
	 * @param bool $async
	 * @return bool|string
	 */
	public function executeSql($async=false) {
		if ($this->isLocked()) {
			throw new Exception('Locked. Could not execute.');
		}

		if ($async) {
			//shell exec
			$cmd = getcwd().'/dbc doExecuteSql '.$this->generateFilename(
				$this->issueNumber,
				$this->issueCount
			);
			proc_close(proc_open($cmd.' &',array(),$foo));
			return 'async';
		} else {
			return $this->doExecuteSql();
		}
	}

	public function getAsyncStatus() {
		$states = array(
			'LOCK' => $this->getLockName(),
			'FAIL' => $this->getFailName(),
			'DONE' => $this->getDoneName()
		);

		foreach ($states AS $status => $src) {
			try {
				$msg = $this->cacher->get($src);
				return array('status' => $status, 'message' => $msg);
			} catch (Exception $e) {
				// do nothing
			}
		}

		return array('status' => 'UNKNOWN', 'message' => '');
	}

	public function doExecuteSql() {
		if ($this->isLocked()) {
			throw new Exception('Locked. Could not execute.');
		}

		if ($this->checkIsExecuted()->getIsExecuted()) {
			throw new Exception($this->generateFilename($this->getIssueNumber(),$this->getIssueCount()).' has already been executed.');
		}

		$sql = trim($this->getSql());

		$res = true;
		if (!empty($sql)) {
			$this->lock(array(
				'change' => $this->generateFilename($this->getIssueNumber(),$this->getIssueCount()),
				'started' => date('Y-m-d H:i:s')
			));

			try {
				$db = new DbConnection($this->settings->getDbConn($this->getDb()),true);
				$db->execute($sql);
			} catch (Exception $e) {
				$this->cacher->store($this->getFailName(),$e->getMessage());
				$res = false;
			}
			$this->unlock();
		}

		if ($res) {
			$this->setIsExecuted(true);
		}

		return $res;
	}

	public function failedToExecuted() {
		//a lock is a fail...
		if ($this->isLocked()) {
			return array('status' => 'FAIL', 'message' => 'Locked...');
		}
		$this->cacher->remove($this->getFailName());
		$this->cacher->store($this->getDoneName(),'marked as executed inspite of fail.');
		return $this->getAsyncStatus();
	}

	/**
	 * Persist data
	 */
	public function save() {
		return $this->changeFactory->getPersistentBackend()->store($this,false);
	}

	/**
	 * @return string
	 */
	protected final function getLockName() {
		//we lock globally and not per change
		return 'DBC_LOCK';
	}

	/**
	 * @return string
	 */
	protected final function getFailName() {
		return $this->getPersistentName('FAIL');
	}

	/**
	 * @return string
	 */
	protected final function getDoneName() {
		return $this->getPersistentName('EXEC');
	}

	public function isExecutable() {
		return (
			$this->isExecuted!==true //not yet executed
			&& (
				DbConnection::canExecuteSuper($this->settings->getDbConn($this->getDb())) //can execute super
					|| $this->rights!='super' //or super is not needed
			)
		);
	}

	/**
	 * @param string $prefix
	 * @return string
	 * @throws Exception
	 */
	protected final function getPersistentName($prefix) {
		if (empty($prefix)) {
			throw new Exception('Prefix must not be empty.');
		}

		return $prefix.'_'.$this->issueNumber.'_'.$this->issueCount;
	}

	protected function lock($msg) {
		$this->cacher->store($this->getLockName(),$msg);
	}

	protected function unlock() {
		$this->cacher->remove($this->getLockName());
	}

	protected function isLocked() {
		return (bool)$this->cacher->exists($this->getLockName());
	}

	/**
	 * Create a DocComment
	 * @param string $action
	 * @param mixed $setting (optional)
	 * @return string
	 */
	private function getDocComment($action,$setting=null) {
		return '#@'.$action.(!is_null($setting) ? ' '.$setting : '');
	}

	/**
	 * @return Change
	 */
	public function checkIsExecuted() {
		$this->isExecuted=$this->cacher->exists($this->getDoneName());
		return $this;
	}

	/**
	 * @param string $author
	 * @return Change
	 */
	public function setAuthor($author) {
		$this->author = $author;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getAuthor() {
		return $this->author;
	}

	/**
	 * @param string $execute
	 * @return Change
	 */
	public function setExecute($execute) {
		$this->execute[] = $execute;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getExecute() {
		return $this->execute;
	}

	/**
	 * @param bool $isExecuted
	 * @return Change
	 */
	public function setIsExecuted($isExecuted=true) {
		if ($this->isLocked()) {
			throw new Exception('Locked. Could not set isExecuted.');
		}

		//if we have a FAIL entry, remove it
		try {
			$this->cacher->remove($this->getFailName());
		} catch (Exception $e) {
			//silent ignore
		}

		if ($isExecuted) {
			$this->cacher->store(
				$this->getDoneName(),array(
					'executed_at' => date('Y-m-d H:i:s'),
					'hash' => md5($this->stringify())
				)
			);
		} else {
			try {
				$this->cacher->remove($this->getDoneName());
			} catch (Exception $e) {
				//silent ignore
			}
		}
		$this->isExecuted = $isExecuted;
		return $this;
	}

	/**
	 * @return mixed|bool
	 */
	public function getIsExecuted() {
		return $this->isExecuted;
	}

	/**
	 * @param int $issueCount
	 * @return Change
	 */
	public function setIssueCount($issueCount) {
		if (empty($issueCount)) {
			throw new Exception('Issue count is mandatory.');
		}
		if (!is_numeric($issueCount) || $issueCount<=0) {
			throw new Exception('Issue count is not a valid number.');
		}
		$this->issueCount = $issueCount;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getIssueCount() {
		return $this->issueCount;
	}

	/**
	 * @param string $issueNumber
	 * @return Change
	 */
	public function setIssueNumber($issueNumber) {
		if (empty($issueNumber)) {
			throw new Exception('Issue number is mandatory.');
		}
		$this->issueNumber = $issueNumber;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getIssueNumber() {
		return $this->issueNumber;
	}

	/**
	 * @param string $message
	 * @return Change
	 */
	public function setMessage($message) {
		$this->message = str_replace("\n",' ',$message);
		return $this;
	}

	/**
	 * @return string
	 */
	public function getMessage() {
		return $this->message;
	}

	/**
	 * @param string $rights
	 * @return Change
	 * @throws Exception - if value is neither normal nor super
	 */
	public function setRights($rights){
		if ($rights!='normal' && $rights!='super') {
			throw new Exception('Right "'.$right.'" is neither "normal" nor "super".');
		}
		$this->rights = $rights;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getRights() {
		return $this->rights;
	}

	/**
	 * @param string $db
	 * @return Change
	 */
	public function setDb($db) {
		$this->db = $db;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getDb() {
		return (!empty($this->db) ? $this->db : 'standard');
	}

	/**
	 * @param timestamp $since
	 * @return Change
	 */
	public function setSince($since) {
		$this->since = $since;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getSince() {
		return $this->since;
	}

	/**
	 * @param bool $slow
	 * @return Change
	 */
	public function setSlow($slow) {
		$this->slow = (bool)$slow;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function getSlow() {
		return (bool)$this->slow;
	}

	/**
	 * @param string $sql
	 * @return Change
	 */
	public function setSql($sql) {
		$this->sql = $sql;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getSql() {
		return $this->sql;
	}

	/**
	 * @return int
	 */
	public function getVersion() {
		return $this->version;
	}
}