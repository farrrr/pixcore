<?php

require_once(dirname(__FILE__) . '/../../init.inc.php');

class Pix_Table_TestDb_User extends Table
{
    public function getLink($type)
    {
        return Pix_Test::getDbAdapter();
    }

    public function __construct()
    {
	$this->_name = 'user';
	$this->_primary = 'id';

	$this->_columns['id'] = array('type' => 'int', 'auto_increment' => true);
	$this->_columns['name'] = array('type' => 'varchar', 'size' => 32);
	$this->_columns['password'] = array('type' => 'varchar', 'size' => 32, 'default' => '');
    }
}

class Pix_Table_TestDb extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
	Pix_Table_TestDb_User::createTable();
    }

    public function test()
    {
	$table = Pix_Table::getTable('Pix_Table_TestDb_User');
	Pix_Table_TestDb_User::insert(array('name' => 'test'));

	// MySQL Server has gone away
        $link = Pix_Test::getDbAdapter();
	$link->kill($link->thread_id);

	// XXX: 本來想測試會不會跳出 warning 的，但是 PHPUnit 抓不到 E_USER_WARNING ，所以這邊要加 @
	@Pix_Table_TestDb_User::insert(array('name' => 'test2'));

	$this->assertEquals(count(Pix_Table_TestDb_User::search(1)), 2);
    }

    public function test2()
    {
	$this->setExpectedException('PHPUnit_Framework_Error'); // Or whichever exception it is

	$table = Pix_Table::getTable('Pix_Table_TestDb_User');
	Pix_Table_TestDb_User::insert(array('name' => 'test'));

	// MySQL Server has gone away
        $link = Pix_Test::getDbAdapter();
	$link->kill($link->thread_id);

	// 這邊不加 @ ，就應該要有 error 了
	Pix_Table_TestDb_User::insert(array('name' => 'test2'));

	$this->assertEquals(count(Pix_Table_TestDb_User::search(1)), 2);
    }

    public function test3()
    {
	$table = Pix_Table::getTable('Pix_Table_TestDb_User');
	Pix_Table_TestDb_User::insert(array('name' => 'test'));

	// MySQL Server has gone away
	define('MYSQL_OPT_READ_TIMEOUT', 11);
        $link = Pix_Test::getDbAdapter();
	$link->query("set wait_timeout=1;");
	sleep(3);

	// 這邊不加 @ ，就應該要有 error 了
	@Pix_Table_TestDb_User::insert(array('name' => 'test2'));

	$this->assertEquals(count(Pix_Table_TestDb_User::search(1)), 2);
    }

    public function tearDown()
    {
	Pix_Table::resetConnect();
	Pix_Table_TestDb_User::dropTable();
    }
}
