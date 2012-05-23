<?php

require_once(dirname(__FILE__) . '/../../init.inc.php');

Pix_Table::$_save_memory = true;

class Pix_Table_TestVolumn_User extends Table
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

class Pix_Table_TestVolumn extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Pix_Table_TestVolumn_User::createTable();
        Pix_Table::addResultSetStaticPlugins('volumnmode');
    }

    public function test()
    {
        $names = array();
        for ($i = 0; $i < 30; $i ++) {
            $u = Pix_Table_TestVolumn_User::insert(array('name' => uniqid()));
            $names[$u->id] = $u->name;
        }

        $count = 0;
        foreach (Pix_Table_TestVolumn_User::search(1)->volumnMode(3) as $u) {
            $this->assertEquals($u->name, $names[$u->id]);
            $count ++;
        }
        $this->assertEquals($count, 30);
    }

    public function tearDown()
    {
	Pix_Table_TestVolumn_User::dropTable();
    }
}
