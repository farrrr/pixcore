<?php

require_once(dirname(__FILE__) . '/../../init.inc.php');

class Pix_Table_TestRelation_User extends Table
{
    public function getLink()
    {
        return Pix_Test::getDbAdapter();
    }

    public function __construct()
    {
	$this->_name = 'user';
	$this->_primary = 'id';

	$this->_columns['id'] = array('type' => 'int', 'auto_increment' => true);
        $this->_columns['theme_id'] = array('type' => 'int', 'default' => 0);

	$this->_relations['theme'] = array('rel' => 'has_one', 'type' => 'Pix_Table_TestRelation_Theme', 'foreign_key' => 'theme_id');
    }
}

class Pix_Table_TestRelation_Theme extends Table
{
    public function getLink()
    {
        return Pix_Test::getDbAdapter();
    }

    public function __construct()
    {
	$this->_name = 'theme';
	$this->_primary = 'id';

	$this->_columns['id'] = array('type' => 'int', 'auto_increment' => true);
        $this->_columns['title'] = array('type' => 'varchar', 'size' => 32, 'default' => '');
	$this->_columns['desc'] = array('type' => 'varchar', 'size' => 32, 'default' => '');
    }
}

class Pix_Table_TestRelation extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Pix_Table_TestRelation_User::createTable();
        Pix_Table_TestRelation_Theme::createTable();
    }

    public function test()
    {
        $user = Pix_Table_TestRelation_User::insert(array());
        $theme1 = Pix_Table_TestRelation_Theme::insert(array('title' => '11111'));
        $theme2 = Pix_Table_TestRelation_Theme::insert(array('title' => '22222'));
        $theme3 = Pix_Table_TestRelation_Theme::insert(array('title' => '33333'));

        // ->{relation_name} = $row
        $user->theme = $theme1;
        $this->assertEquals($user->theme_id, $theme1->id);
        $this->assertEquals($user->theme->id, $theme1->id);

        // ->{relation_name} = $value
        $user->theme = $theme2->id;
        $this->assertEquals($user->theme_id, $theme2->id);
        $this->assertEquals($user->theme->id, $theme2->id);

        // ->{relation_name} = array($values)
        $user->theme = $theme3->getPrimaryValues();
        $this->assertEquals($user->theme_id, $theme3->id);
        $this->assertEquals($user->theme->id, $theme3->id);
    }

    public function tearDown()
    {
	Pix_Table_TestRelation_User::dropTable();
        Pix_Table_TestRelation_Theme::dropTable();
    }
}
