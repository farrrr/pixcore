<?php

/**
 * Pix_Db
 * 1. lazy connection
 * 2. master/slave adaption
 *
 * @package Pix_Db
 * @copyright 2003-2010 PIXNET
 * @author Gea-Suan Lin <gslin@pixnet.tw>
 */
class Pix_Db
{
    protected $conf = null;
    static protected $debug = FALSE;
    public $mdb = null;
    public $sdb = null;

    /* If username is not null, then $confFilename is DSN. */
    public function __construct($confFilename, $username = null, $password = null)
    {
	$this->debugInfo('__construct, $confFilename = [%s]', $confFilename);

	if (is_null($username)) {
	    $str = @file_get_contents($confFilename);
            if (FALSE === $str) {
		throw new Exception('No match config file for Pix_Db');
	    }

	    $this->conf = json_decode($str);

	    /* If there is no DSN, use host/port/dbname/unix_socket to aggregate it. */
	    foreach ($this->conf as &$conf) {
		foreach ($conf as $type => $v) {
		    if (property_exists($v, 'dsn')) {
			continue;
		    }

		    $strArray = array();
		    foreach (array('host', 'port', 'dbname', 'unix_socket') as $k) {
			if (property_exists($v, $k)) {
			    $strArray[] = sprintf('%s=%s', $k, $v->$k);
			}
		    }

		    $conf->$type->dsn = 'mysql:' . implode(';', $strArray);
		}
	    }
	    unset($conf);
	} else {
	    $obj = new StdClass;
	    $obj->master = new StdClass;
	    $obj->master->dsn = $confFilename;
	    $obj->master->username = $username;
	    $obj->master->password = $password;

	    $this->conf = array($obj);
	}

	return;
    }

    public function begin()
    {
	$this->debugInfo('Transaction begin');

        $this->connect();
        if ($this->mdb->beginTransaction()) {
            return $this;
        }

        return FALSE;
    }

    public function commit()
    {
	$this->debugInfo('Transaction commit');

	$r = $this->mdb->commit();

	return $r;
    }

    public function connect()
    {
	$this->debugInfo('Connect called');

	// Master & slave are not null, and no error means they are connected.
	if (!is_null($this->mdb) and !intval($this->mdb->errorCode()) and !is_null($this->sdb) and !intval($this->sdb->errorCode())) {
	    return;
	}

	$this->debugInfo('Connect');

	$confFilename = $this->confFilename;

	shuffle($this->conf);

	// candicate
	foreach ($this->conf as $dbConf) {
	    $m = $dbConf->master;
	    $s = $dbConf->slave;

	    for ($i = 0; $i < 3; $i ++) {
		try {
		    $this->mdb = new PDO($m->dsn, $m->username, $m->password);
		    break;
		} catch (PDOException $exception) {
		}
	    }

	    if (is_null($this->mdb)) {
		continue;
	    } elseif ($m->timeout > 0) {
		$this->mdb->setAttribute(PDO::ATTR_TIMEOUT, $m->timeout);
	    }

	    if (!is_null($m->charset)) {
		$this->mdb->query(sprintf('SET NAMES %s', $m->charset));
	    }

	    if ($s) {
		$this->sdb = new PDO($s->dsn, $s->username, $s->password);
	    }

	    // If it's not available, assign master db to it.
	    if (is_null($this->sdb)) {
		$this->sdb = $this->mdb;
		break;
	    }

	    if ($s->timeout > 0) {
		$this->sdb->setAttribute(PDO::ATTR_TIMEOUT, $m->timeout);
	    }

	    if (!is_null($s->charset)) {
		$this->sdb->query(sprintf('SET NAMES %s', $s->charset));
	    }

	    break;
	}

	if (is_null($this->mdb)) {
	    throw new Exception('Unable to connect to master');
	}

	return $this;
    }

    public function debug($d = TRUE)
    {
	self::$debug = $d;
    }

    protected function debugInfo($str)
    {
	if (!self::$debug) {
	    return;
	}

	$args = func_get_args();

	$str = call_user_func_array('sprintf', $args);
	error_log(sprintf('[%s] %s', $this->confFilename, $str));
    }

    public function fetch($sql)
    {
	$this->debugInfo('fetch: [%s]', $sql);

	$args = $this->fetchCommon(func_get_args());

	$p = $this->sdb->prepare($sql);
	if (!$p->execute($args)) {
	    $e = $p->errorInfo();
	    throw new Pix_Db_Exception($e[2], $e[0]);
	}

	return $p;
    }

    public function fetchAll($sql)
    {
	$this->debugInfo('fetchAll: [%s]', $sql);

	$args = $this->fetchCommon(func_get_args());

	$p = $this->sdb->prepare($sql);
	if (!$p->execute($args)) {
	    $e = $p->errorInfo();
	    throw new Pix_Db_Exception($e[2], $e[0]);
	}

	return $p->fetchAll(PDO::FETCH_OBJ);
    }

    public function fetchColumn($sql)
    {
	$this->debugInfo('fetchColumn: [%s]', $sql);

	$args = $this->fetchCommon(func_get_args());

	$p = $this->sdb->prepare($sql);
	if (!$p->execute($args)) {
	    $e = $p->errorInfo();
	    throw new Pix_Db_Exception($e[2], $e[0]);
	}

	return $p->fetchColumn();
    }

    protected function fetchCommon($args)
    {
	$this->connect();

	if (is_array($args[1])) {
	    $args = $args[1];
	} else {
	    array_shift($args);
	}

	return $args;
    }

    public function fetchOne($sql)
    {
	$this->debugInfo('fetchOne: [%s]', $sql);

	$args = $this->fetchCommon(func_get_args());

	$p = $this->sdb->prepare($sql);
	if (!$p->execute($args)) {
	    $e = $p->errorInfo();
	    throw new Pix_Db_Exception($e[2], $e[0]);
	}

	$r = $p->fetch(PDO::FETCH_NUM);

	return $r[0];
    }

    public function fetchRow($sql)
    {
	$this->debugInfo('fetchRow: [%s]', $sql);

	$args = $this->fetchCommon(func_get_args());

	$p = $this->sdb->prepare($sql);
	if (!$p->execute($args)) {
	    $e = $p->errorInfo();
	    throw new Pix_Db_Exception($e[2], $e[0]);
	}

	return $p->fetch(PDO::FETCH_OBJ);
    }

    public function insertIgnoreCommand()
    {
        $this->connect();
        $driverName = $this->mdb->getAttribute(PDO::ATTR_DRIVER_NAME);

        // Support SQLite and MySQL
        if ('mysql' == $driverName) {
            return 'INSERT IGNORE INTO';
        } elseif ('sqlite' == $driverName) {
            return 'INSERT OR IGNORE INTO';
        }

        // 不支援的故意傳回空值
        return '';
    }

    public function isWritingSQL($sql)
    {
	// "SELECT" but not "SELECT ... INTO"
	if (!preg_match('/^\s*SELECT\>/i', $sql) or preg_match('/^\s*SELECT\>.*\<INTO\>/i', $sql)) {
            return TRUE;
	} else {
            return FALSE;
	}
    }

    public function lastInsertId()
    {
	return $this->mdb->lastInsertId();
    }

    public function prepare($str)
    {
        $this->debugInfo('prepare: [%s]', $str);

        $this->connect();
        if ($this->isWritingSQL($sql)) {
            $db = $this->mdb;
        } else {
            $db = $this->sdb;
        }

        return $db->prepare($str);
    }

    public function query($sql)
    {
	$this->debugInfo('query: [%s]', $sql);

	$this->connect();
	if ($this->isWritingSQL($sql)) {
	    $db = $this->mdb;
	} else {
	    $db = $this->sdb;
	}

	$args = func_get_args();
	if (is_array($args[1])) {
	    $args = $args[1];
	} else {
	    array_shift($args);
	}

	$r = $db->prepare($sql);
	if (!$r->execute($args)) {
            $e = $r->errorInfo();
	    throw new Pix_Db_Exception(strval($e[2]), intval($e[0]));
	}

	return $r->rowCount();
    }

    public function quote($str)
    {
	$this->connect();

	return $this->sdb->quote($str);
    }

    public function rollback()
    {
	$this->debugInfo('Transaction rollback');

	$r = $this->mdb->rollBack();

	return $r;
    }
}
