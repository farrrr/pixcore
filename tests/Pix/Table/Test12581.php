<?php
/**
 *  測試 relation 修改
 */

require_once(dirname(__FILE__) . '/../../init.inc.php');

class Pix_Table_Test12581_User extends Table
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

	$this->_relations['articles'] = array('rel' => 'has_many', 'type' => 'Pix_Table_Test12581_Article', 'foreign_key' => 'user_id');
    }
}

class Pix_Table_Test12581_Product extends Table
{
    public function getLink($type)
    {
        return Pix_Test::getDbAdapter();
    }

    public function __construct()
    {
	$this->_name = 'product';
	$this->_primary = 'id';

	$this->_columns['id'] = array('type' => 'int', 'auto_increment' => true);
	$this->_columns['title'] = array('type' => 'varchar', 'size' => 32, 'default' => '');

	$this->_relations['user'] = array('rel' => 'has_one', 'type' => 'Pix_Table_Test12581_User', 'foreign_key' => 'id', 'delete' => true);
	$this->_relations['gift'] = array('rel' => 'has_one', 'type' => 'Pix_Table_Test12581_Gift', 'foreign_key' => 'id');
    }
}

class Pix_Table_Test12581_Gift extends Table
{
    public function getLink($type)
    {
        return Pix_Test::getDbAdapter();
    }

    public function __construct()
    {
	$this->_name = 'gift';
	$this->_primary = 'id';

	$this->_columns['id'] = array('type' => 'int', 'auto_increment' => true);
	$this->_columns['user_id'] = array('type' => 'int', 'default' => 0);
	$this->_columns['code'] = array('type' => 'varchar', 'size' => 32, 'default' => '');
	$this->_columns['title'] = array('type' => 'varchar', 'size' => 32, 'default' => '');

	$this->_relations['product'] = array('rel' => 'has_one', 'type' => 'Pix_Table_Test12581_Product', 'foreign_key' => 'id', 'delete' => true);
	$this->_relations['user'] = array('rel' => 'has_one', 'type' => 'Pix_Table_Test12581_User', 'foreign_key' => 'id', 'delete' => true);
    }
}

class Pix_Table_Test12581_Article extends Table
{
    public function getLink($type)
    {
        return Pix_Test::getDbAdapter();
    }

    public function __construct()
    {
	$this->_name = 'article';
	$this->_primary = 'id';

	$this->_columns['id'] = array('type' => 'int', 'auto_increment' => true);
	$this->_columns['user_id'] = array('type' => 'varchar', 'size' => 32);
	$this->_columns['title'] = array('type' => 'varchar', 'size' => 32, 'default' => '');

	$this->_relations['user'] = array('rel' => 'has_one', 'type' => 'Pix_Table_Test12581_User', 'foreign_key' => 'user_id', 'delete' => true);
    }
}


class Pix_Table_Test12581 extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
	Pix_Table_Test12581_User::createTable();
	Pix_Table_Test12581_Article::createTable();
	Pix_Table_Test12581_Gift::createTable();
	Pix_Table_Test12581_Product::createTable();
    }

    public function test()
    {
	$user_1 = Pix_Table_Test12581_User::insert(array('name' => 'user1'));
	$user_2 = Pix_Table_Test12581_User::insert(array('name' => 'user2'));

	$art = $user_1->articles->insert(array('title' => 'test'));

	$this->assertEquals($art->user->id, $user_1->id);

	$art->update(array('user_id' => $user_2->id));

	$this->assertEquals($art->user_id, $user_2->id);
	$this->assertEquals($art->user->id, $user_2->id);
    }

    public function test2()
    {
	Pix_Table::$_save_memory = true;
	$user = Pix_Table_Test12581_User::insert(array('name' => 'user', 'password' => 'pwd1'));
	$gift = Pix_Table_Test12581_Gift::insert(array('code' => 'test', 'title' => 'test'));
	$product = Pix_Table_Test12581_Product::insert(array('title' => 'test'));
	Pix_Table::$_save_memory = false;

	$gift = Pix_Table_Test12581_Gift::search(array('code' => 'test'))->first();
	$gift->update(array('title' => 'test2'));
	$product = Pix_Table_Test12581_Product::find($gift->id);

	$this->assertEquals($gift->title, $product->gift->title);
    }

    public function tearDown()
    {
	Pix_Table_Test12581_User::dropTable();
	Pix_Table_Test12581_Article::dropTable();
	Pix_Table_Test12581_Gift::dropTable();
	Pix_Table_Test12581_Product::dropTable();
    }
}
