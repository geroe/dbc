<?php
/**
 * Created by JetBrains PhpStorm.
 * User: georgroesch
 * Date: 13.09.12
 * Time: 23:52
 */
class CacheDb extends DbConnection implements IPersistentCache {
	/**
	 * @var DbChangesSettings
	 */
	private $settings;

	private $cacheTable = 'dbc_cache';

	public function __construct(DbChangesSettings $settings) {
		$this->settings = $settings;

		$dbConn = $settings->getDbConn('cache');

		//if there is no cache connection use the standard one
		if (empty($dbConn)) {
			$dbConn = $settings->getDbConn('standard');
		}
		parent::__construct($dbConn);

		//check table
		if (isset($dbConn['cache_table'])) {
			$this->cacheTable=(string)$dbConn['cache_table'];
		}

		//$this->checkCacheTable();
	}

	private function checkCacheTable() {
		$pdo = $this->connection;

		$unique = uniqid('TESTCONNECTION_');
		$now = $this->now();

		if (!$pdo->exec('
			INSERT INTO '.$this->cacheTable.' (
				name,
				value,
				user,
				tmstmp
			) VALUES (
				'.$this->quote($unique).',
				'.$this->quote('Test').',
				'.$this->quote('SYSTEM').',
				'.$this->quote($now).'
			)
		')) {
			throw new Exception('Could not write to DB. '.$this->getLastError());
		}

		$res = $pdo->query('SELECT * FROM '.$this->cacheTable.' WHERE name='.$this->quote($unique));
		if (!sizeof($res)) {
			throw new Exception('Could not retrieve test entry. DB connection unstable. Aborting.');
		}

		$res = $res->fetch();
		if (
			$res['name']!=$unique
			|| $res['value']!='Test'
			|| $res['user']!='SYSTEM'
			|| $res['tmstmp']!=$now
		) {
			throw new Exception('Fetched data does not compare. DB connection unstable. Aborting.');
		}

		if (!$pdo->exec('DELETE FROM '.$this->cacheTable.' WHERE name='.$this->quote($unique))) {
			throw new Exception('Could not delete Test entry. DB connection unstable. Aborting.');
		}

		return true;
	}


	public function store($name, $content, $append = false) {
		if (is_array($content)) {
			$content = json_encode($content);
		}
		if ($append) {
			$old_content='';
			try {
				$old_content = $this->get($name);
			} catch (Exception $e) {
				//silent ignore
			}
			$content.=$old_content.$content;
		}

		try {
			$this->remove($name);
		} catch (Exception $e) {
			//silent ignore
		}

		if (false===$this->connection->exec('
			INSERT INTO '.$this->cacheTable.' (
				name,
				value,
				user,
				tmstmp
			) VALUES (
				'.$this->quote($name).',
				'.$this->quote($content).',
				'.$this->quote('UNKNOWN').',
				'.$this->quote($this->now()).'
			)
		')) {
			throw new Exception($this->getLastError());
		}
	}

	public function get($name) {
		$res = $this->connection->query('
			SELECT
				value
			FROM
				'.$this->cacheTable.'
			WHERE
				name='.$this->quote($name)
		,PDO::FETCH_COLUMN,0);

		$val = $res->fetch();

		if (!$val) {
			throw new Exception($name.' not found. '.$this->getLastError());
		}

		$json = json_decode($val,true);
		return (is_null($json) ? $val : $json);
	}

	public function remove($name) {
		$sql='DELETE FROM '.$this->cacheTable.' WHERE name='.$this->quote($name);
		if ($this->connection->exec($sql)===false) {
			throw new Exception('Could not delete "'.$name.'". '.$this->getLastError());
		}
	}

	public function exists($name) {
		$res = $this->connection->query('
			SELECT
				*
			FROM
				'.$this->cacheTable.'
			WHERE
				name='.$this->quote($name)
		);

		$bool = $res->rowCount();
		return (bool)$bool;
	}

	public function who($name) {
		$res = $this->connection->query('
			SELECT
				value
			FROM
				'.$this->cacheTable.'
			WHERE
				name='.$this->quote($name)
			,PDO::FETCH_COLUMN,0);

		$val = $res->fetch();

		if (!$val) {
			throw new Exception($name.' not found. '.$this->getLastError());
		}

		return $val;
	}

	public function when($name) {
		$res = $this->connection->query('
			SELECT
				tmstmp
			FROM
				'.$this->cacheTable.'
			WHERE
				name='.$this->quote($name)
			,PDO::FETCH_COLUMN,0);

		if (!$res) {
			throw new Exception($name.' not found. '.$this->getLastError());
		}

		return $res->fetchColumn();
	}
}
