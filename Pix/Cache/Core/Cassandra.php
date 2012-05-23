<?php

class Pix_Cache_Core_Cassandra extends Pix_Cache_Core
{
    const CONSISTENCY_ONE = 'CONSISTENCY ONE';
    const DEFAULT_TIMEOUT_IN_MILLISECOND = 3000;

    protected $_pdo;
    protected $_available_hosts;
    protected $_config;

    protected $_read_consistency = self::CONSISTENCY_ONE;
    protected $_write_consistency = self::CONSISTENCY_ONE;

    protected function getDSN()
    {
        $config = $this->_config;
        foreach ($config['hosts'] as $config) {
            $hosts[] = "host={$config['host']};port={$config['port']}";
        }
        return 'cassandra:' . implode(',', $hosts);
    }

    protected function getPDO()
    {
        $config = $this->_config;
        if (!isset($this->_pdo)) {
            $dsn = $this->getDSN();
            $options = array(
                PDO::ATTR_TIMEOUT => $config['timeout'],
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            );
            $this->_pdo = new PDO($dsn, $config['username'], $config['password'], $options);
        }
        $this->_pdo->exec("USE {$config['keyspace']}");

        return $this->_pdo;
    }

    public function getConfig()
    {
        return $this->_config;
    }

    public function __construct($config)
    {
        foreach (array('hosts', 'column_family', 'keyspace') as $required_key) {
            if (!isset($config[$required_key])) {
                throw new Pix_Exception("$required_key required");
            }
        }
        if (!$timeout = intval($config['timeout'])) {
            $config['timeout'] = self::DEFAULT_TIMEOUT_IN_MILLISECOND;
        }
        $this->_config = $config;
    }

    public function add($key, $value, $expire = null)
    {
        if ($this->get($key) === FALSE) {
            return $this->set($key, $value, $expire);
        }
        return FALSE;
    }

    public function set($key, $value, $expire = null)
    {
        $cql = "INSERT INTO {$this->_config['column_family']} (KEY, :column) VALUES(:key, :value) "
            . "USING {$this->_write_consistency} AND TTL :ttl";

        if (is_null($expire)) {
            $expire = $this->_config['options']['expire'];
        }

        $pdo = $this->getPDO();
        $stmt = $pdo->prepare($cql);
        $stmt->bindValue('column', '0');
        $stmt->bindValue('key', $key);
        $stmt->bindValue('value', $value);
        $stmt->bindValue('ttl', intval($expire), PDO::PARAM_INT);

        if ($success = $stmt->execute()) {
            if ($auto_history = intval($this->_config['auto_history'])) {
                $cql = "INSERT INTO {$this->_config['column_family']} (KEY, :column) VALUES(:key, :value) USING {$this->_write_consistency}";
                $stmt->bindValue('column', intval(microtime(TRUE) * 1000));
                $stmt->bindValue('ttl', $auto_history, PDO::PARAM_INT);
                return $stmt->execute();
            }
        }

        return $success;
    }

    public function delete($key)
    {
        $pdo = $this->getPDO();

        $stmt = $pdo->prepare("DELETE 0 FROM :cf USING {$this->_write_consistency}"
            . " WHERE KEY = :key");
        $stmt->bindValue('cf', $this->_config['column_family']);
        $stmt->bindValue('key', $key);

        return $stmt->execute();
    }

    public function replace($key, $value, $expire = null)
    {
        if ($this->get($key) === FALSE) {
            return FALSE;
        }
        return $this->set($key, $value, $expire);
    }

    public function inc($key)
    {
        throw new Pix_Exception('inc operation not support');
    }

    public function dec($key)
    {
        throw new Pix_Exception('dec operation not support');
    }

    public function get($key)
    {
        $column = 0;

        $pdo = $this->getPDO();
        $stmt = $pdo->prepare("SELECT :column FROM {$this->_config['column_family']} USING $this->_read_consistency "
            . "WHERE KEY = :key");
        $stmt->bindValue('column', $column, PDO::PARAM_INT);
        $stmt->bindValue('key', $key);

        if ($stmt->execute()) {
            if ($result = $stmt->fetch(PDO::FETCH_ASSOC)
                and isset($result[$column])
                and !empty($result[$column]))
            {
                return $result[$column];
            }
        }
        return FALSE;
    }

    public function gets($keys)
    {
        $pdo = $this->getPDO();

        $escaped_keys = array();
        foreach ($keys as $key) {
            $escaped_keys[] = $pdo->quote($key);
        }

        $column = 0;
        $cql = "SELECT KEY, :column FROM :cf USING $this->_read_consistency "
            . "WHERE KEY IN %s";
        $cql = sprintf($cql, '('.implode(', ', $escaped_keys).')');

        $stmt = $pdo->prepare($cql);
        $stmt->bindValue('column', $column, PDO::PARAM_INT);
        $stmt->bindValue('cf', $this->_config['column_family']);

        $ret = array();
        if (!$stmt->execute()) {
            return $ret;
        }
        while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (is_null($result[$column])) {
                continue;
            }
            $ret[$result['KEY']] = $result[$column];
        }
        return $ret;
    }
}
