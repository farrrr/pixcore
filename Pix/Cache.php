<?php

/**
 * Pix_Cache 
 * 
 * @package Pix_Cache
 * @version $id$
 * @copyright 2003-2009 PIXNET
 * @license 
 */
class Pix_Cache
{
    protected $_id = 0;

    public function __construct($id = 0)
    {
	$this->_id = $id;
    }

    public function __call($func, $args)
    {
	if (!self::$_servers[$this->_id]) {
	    trigger_error('你應該要先做 Pix_Cache::addServer()', E_USER_WARNING);
	    return null;
	}
	$ret = call_user_func_array(array(self::$_servers[$this->_id], $func), $args);
	return $ret;
    }
    

    protected static $_servers = array();

    /**
     * addServer 增加 Cache Server 設定。
     * 
     * @param mixed $core  Pix_Cache 使用的 Core 的 class name (Ex: Pix_Cache_Core_Memcache)
     * @param array $conf  
     * @param int $id 
     * @static
     * @access public
     * @return void
     */
    public static function addServer($core, $conf = array(), $id = 0)
    {
	if (!class_exists($core)) {
	    throw new Pix_Exception('Class not found');
	}

	$server = new $core($conf);
	if (!is_a($server, 'Pix_Cache_Core')) {
	    throw new Pix_Exception("$core is not a Pix_Cache_Core");
	}
	self::$_servers[$id] = $server;
    }

    public static function reset()
    {
	self::$_servers = array();
    }
}
