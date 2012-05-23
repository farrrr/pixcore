<?php
/**
 *  http://bug.mgmt.pixnet/trac/ticket/12583
 *  測試 primary key 加上 array() 和不加會不會有差別
 */

require_once(dirname(__FILE__) . '/../../init.inc.php');

class Pix_Table_Test12583_User extends Table
{
    public function getLink($type)
    {
        return Pix_Test::getDbAdapter();
    }

    public function __construct()
    {
	$this->_name = 'user';
	$this->_primary = array('id');
	$this->_columns['id'] = array('type' => 'int', 'auto_increment' => true);
	$this->_columns['name'] = array('type' => 'varchar', 'size' => 32);
	$this->_columns['password'] = array('type' => 'varchar', 'size' => 32, 'default' => '');
    }
}


class Pix_Table_Test12583 extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
	Pix_Table_Test12583_User::createTable();
    }

    public function test()
    {
	$table = Pix_Table::getTable('Pix_Table_Test12583_User');

	$table->_primary = array('id');
	$row = $table->insert(array('name' => 'testtest'));
	$this->assertEquals($row->id, 1);

	$table->_primary = 'id';
	$row = $table->insert(array('name' => 'testtest2'));
	$this->assertEquals($row->id, 2);
    }

    public function tearDown()
    {
	Pix_Table_Test12583_User::dropTable();
    }
}
