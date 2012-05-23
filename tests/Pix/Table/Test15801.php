<?php
/**
 *  在 enableTableCache 的情況下，會抓不到 binary column 的資料
 */

require_once(dirname(__FILE__) . '/../../init.inc.php');

class Pix_Table_Test15801_User extends Table
{
    public function getLink($type)
    {
        return Pix_Test::getDbAdapter();
    }

    public function __construct()
    {
        $this->enableTableCache();
	$this->_name = 'user';
	$this->_primary = 'id';

	$this->_columns['id'] = array('type' => 'int', 'auto_increment' => true);
	$this->_columns['name'] = array('type' => 'binary', 'size' => 32);
    }
}


class Pix_Table_Test15801 extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Pix_Cache::addServer('Pix_Cache_Core_Memcache', array('host' => PIXCORE_TEST_MEMCACHEHOST, 'port' => PIXCORE_TEST_MEMCACHEPORT), 'test');
        Pix_Table::$_save_memory = true;
        Pix_Table::setCache(new Pix_Cache('test'));
        Pix_Table_Test15801_User::createTable();
    }

    public function test()
    {
        $user_1 = Pix_Table_Test15801_User::insert(array('name' => md5(uniqid(mt_rand(), true), true)));
        $user_1_find = Pix_Table_Test15801_User::find($user_1->id);
        $this->assertEquals($user_1->name, $user_1_find->name);
    }

    public function tearDown()
    {
	Pix_Table_Test15801_User::dropTable();
        Pix_Table::setCache(null);
    }
}
