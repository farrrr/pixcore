<?php

require_once(dirname(__FILE__) . '/../../init.inc.php');

Pix_Table::$_save_memory = true;

class Pix_Table_TestFindBy_User extends Table
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
	$this->_columns['extra'] = array('type' => 'int');
    }
}

class Pix_Table_TestFindBy extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
	Pix_Table_TestFindBy_User::createTable();
    }

    public function test()
    {
	Pix_Table_TestFindBy_User::insert(array('id' => 1, 'name' => 'testtest', 'password' => '123', 'extra' => 2));
	Pix_Table_TestFindBy_User::insert(array('id' => 2, 'name' => 'testtest2', 'password' => '123', 'extra' => 1));
	Pix_Table_TestFindBy_User::insert(array('id' => 3, 'name' => 'testtest3', 'password' => '1234', 'extra' => 1));

	$row = Pix_Table_TestFindBy_User::find_by_id(1);
	$this->assertEquals($row->id, 1);

	$row = Pix_Table_TestFindBy_User::find_by_name('testtest2');
	$this->assertEquals($row->id, 2);

	$row = Pix_Table_TestFindBy_User::find_by_password('123');
	$this->assertEquals($row->id, 1);

	$row = Pix_Table_TestFindBy_User::find_by_extra_and_password(1, '1234');
	$this->assertEquals($row->id, 3);

	$row = Pix_Table_TestFindBy_User::find_by_extra_and_password(1, '123');
	$this->assertEquals($row->id, 2);

	$this->assertEquals(null, Pix_Table_TestFindBy_User::find_by_id(10));
	$this->assertEquals(null, Pix_Table_TestFindBy_User::find_by_name('notfound'));
	$this->assertEquals(null, Pix_Table_TestFindBy_User::find_by_password('12'));
	$this->assertEquals(null, Pix_Table_TestFindBy_User::find_by_extra_and_password(1, '12345'));
    }

    public function tearDown()
    {
	Pix_Table_TestFindBy_User::dropTable();
    }
}
