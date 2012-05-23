<?php

require_once(dirname(__FILE__) . '/../../../init.inc.php');

class Pix_Table_Test_User extends Table
{
    public function getLink($type)
    {
	$link = new mysqli(PIXCORE_TEST_MYSQLHOST, PIXCORE_TEST_MYSQLUSER, PIXCORE_TEST_MYSQLPASS);
	$link->select_db(PIXCORE_TEST_MYSQLDB);
	return $link;
    }

    public function __construct()
    {
	$this->_name = 'user';
	$this->_primary = 'id';
	$this->_columns['id'] = array('type' => 'int', 'auto_increment' => true);
	$this->_columns['name'] = array('type' => 'varchar', 'size' => 32);
	$this->_columns['password'] = array('type' => 'varchar', 'size' => 32, 'default' => '');
	$this->_columns['install_count'] = array('type' => 'int', 'default' => 0);

	$this->addPlugins(array('increment', 'decrement'), 'Pix_Table_Plugin_Fluctuate');
    }
}

class Pix_Table_Plugin_TestFluctuate extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
	Pix_Table_Test_User::createTable();
    }

    public function test()
    {
	$row = Pix_Table_Test_User::insert(array('name' => 'testtest', 'install_count' => 0));
	$this->assertEquals($row->install_count, 0);

	$row->increment('install_count', 1);
	$this->assertEquals($row->install_count, 1);

	$row->increment('install_count', 20);
	$this->assertEquals($row->install_count, 21);

	$row->decrement('install_count', 1);
	$this->assertEquals($row->install_count, 20);

	$row->decrement('install_count', 10);
	$this->assertEquals($row->install_count, 10);

    }

    public function tearDown()
    {
	Pix_Table_Test_User::dropTable();
    }
}
?>
