<?php
/**
 * Created by JetBrains PhpStorm.
 * User: georgroesch
 * Date: 13.09.12
 * Time: 21:46
 */
class DbConnection {


	/**
	 * @var PDO
	 */
	protected $connection;

	/**
	 * @var bool
	 */
	protected $transactional = true;

	/**
	 * @var bool
	 */
	protected $super = false;

	/**
	 * @var array
	 * @static
	 */
	protected static $connectionPool = array();

	
	/**
	 * @param array $settings
	 * @throws Exception
	 */
	public function __construct(array $settings,$createNewConnection=false) {
		if (!isset($settings['dsn'])) {
			throw new Exception('No DSN found for your DB connection.');
		}

		$dsn = $settings['dsn'];
		$user = (isset($settings['user']) ? (string)$settings['user'] : null);
		$password = (isset($settings['password']) ? (string)$settings['password'] : null);

		$this->transactional = (isset($settings['transactional']) ? (bool)$settings['transactional'] : false);
		$this->super = (isset($settings['super']) ? (bool)$settings['super'] : false);

		if ((bool)$createNewConnection) {
			$this->connection = new PDO($dsn,$user,$password);
			return;
		}

		//calc hash for connection pooling (singleton)
		$hash = md5($dsn.$user.$password);

		if (!array_key_exists($hash,self::$connectionPool)) {
			self::$connectionPool[$hash] = new PDO($dsn,$user,$password);
		}

		$this->connection = self::$connectionPool[$hash];
	}

	/**
	 * @param string $txt
	 * @return string
	 */
	public function quote($txt) {
		return $this->connection->quote($txt);
	}

	/**
	 * @param string $sql
	 * @return bool
	 * @throws Exception
	 */
	public function execute($sql) {
		$pdo = $this->connection;
		if ($this->transactional) {
			$pdo->setAttribute(PDO::ATTR_AUTOCOMMIT,FALSE);
			if (!$pdo->beginTransaction()) {
				throw new Exception('Could not start transaction.');
			}
		}

		try {
			//execute query
			if($pdo->exec($sql)===false) {
				throw new Exception($this->getLastError());
			}
			if ($this->transactional) {
				if (!$pdo->commit()) {
					throw new Exception('Could not commit transaction.');
				}
			}
		} catch (Exception $e) {
			if ($this->transactional) {
				if (!$pdo->rollBack()) {
					throw new Exception('An error occured and the transaction could not be rolled back. '.$e->getMessage());
				}
			}
			throw $e;
		}

		return true;
	}

	protected function now() {
		return date('Y-m-d H:i:s');
	}

	protected function getLastError() {
		$errInfo = $this->connection->errorInfo();
		return 'DB Error: '.$errInfo[0].' ('.$errInfo[1].') '.$errInfo[2];
	}

	/**
	 * is this connection able to execute queries that need "super" privileges
	 * @return bool
	 */
	public function getSuper() {
		return $this->super;
	}

	public static function canExecuteSuper(array $settings) {
		return (isset($settings['super']) ? (bool)$settings['super'] : false);
	}
}
