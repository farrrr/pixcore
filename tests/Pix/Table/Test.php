<?php

require_once(dirname(__FILE__) . '/../../init.inc.php');

Pix_Table::$_save_memory = true;

class Pix_Table_Test_User extends Table
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

	$this->_relations['articles'] = array('rel' => 'has_many', 'type' => 'Pix_Table_Test_Article', 'foreign_key' => 'user_id');
	$this->_relations['blog'] = array('rel' => 'has_one', 'type' => 'Pix_Table_Test_Blog', 'foreign_key' => 'id');
    }
}

class Pix_Table_Test_Blog extends Table
{
    public function getLink($type)
    {
        return Pix_Test::getDbAdapter();
    }

    public function __construct()
    {
	$this->_name = 'blog';
	$this->_primary = 'id';

	$this->_columns['id'] = array('type' => 'int', 'auto_increment' => true);
        $this->_columns['title'] = array('type' => 'varchar', 'size' => 32, 'default' => '');
	$this->_columns['desc'] = array('type' => 'varchar', 'size' => 32, 'default' => '');
    }
}

class Pix_Table_Test_Article extends Table
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

	$this->_relations['user'] = array('rel' => 'has_one', 'type' => 'Pix_Table_Test_User', 'foreign_key' => 'user_id', 'delete' => true);
    }
}


class Pix_Table_Test extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if (!extension_loaded('mysqli')) {
            return $this->markTestSkipped('The mysqli extension is not available.');
        }
        Pix_Table_Test_User::createTable();
        Pix_Table_Test_Blog::createTable();
	Pix_Table_Test_Article::createTable();
    }

    public function test()
    {
        $row = Pix_Table_Test_User::insert(array('name' => 'testtest'));
	$this->assertEquals($row->getTableClass(), 'Pix_Table_Test_User');
	$this->assertEquals($row->name, 'testtest');
	$this->assertEquals(Pix_Table_Test_User::search(1)->count(), 1);

	$id = $row->id;
	$row = Pix_Table_Test_User::find($id);
	$this->assertEquals($row->getTableClass(), 'Pix_Table_Test_User');
	$this->assertEquals($row->name, 'testtest');

	$rows = Pix_Table_Test_User::search(array('name' => 'testtest'));
	$this->assertEquals($rows->count(), 1);

	$row = $rows->first();
	$this->assertEquals($row->getTableClass(), 'Pix_Table_Test_User');
	$this->assertEquals($row->name, 'testtest');

	$rows = Pix_Table_Test_User::search(array('name' => 'notfound'));
	$this->assertEquals($rows->count(), 0);
	$this->assertEquals(null, $rows->first());

	$row = Pix_Table_Test_User::createRow();
	$row->name = 'test2';
	$this->assertEquals($row->id, null);
        $row->save();

        $row->name = 123;
        $row->save();
        $this->assertEquals($row->name, 123);

        $row->update('name = name + 1');
        $this->assertEquals($row->name, 124);


	$this->assertEquals(Pix_Table_Test_User::search(1)->count(), 2);
    }

    public function testRelation()
    {
	$user = Pix_Table_Test_User::insert(array('name' => 'relationtest'));

	$art = $user->articles->insert(array('title' => 'test1'));

	$this->assertEquals(count($user->articles), 1);
	$this->assertEquals($art->user->name, 'relationtest');

	$art = $user->articles->createRow();
	$art->title = 'test2';
	$art->save();
	$this->assertEquals(count($user->articles), 2);

	$array = array('test1', 'test2');
	$count = 0;
	foreach ($user->articles as $art) {
	    $this->assertEquals($art->title, $array[$count]);
	    $count ++;
        }

        try {
            $user->articles->insert(array('id' => $art->id));
            $this->assertTrue(false);
        } catch (Pix_Table_DuplicateException $e) {
            $this->assertTrue(true);

        }

	$user->delete();
    }

    public function testCreate()
    {
        $user = Pix_Table_Test_User::insert(array('name' => 'createtest'));

        $blog = $user->create_blog(array('title' => 'I AM TITLE', 'desc' => 'WHAT?'));
        $this->assertEquals($blog->id, $user->id);
        $this->assertEquals($blog->title, 'I AM TITLE');
        $this->assertEquals($blog->desc, 'WHAT?');
    }

    public function testDuplcate()
    {
        $user = Pix_Table_Test_User::insert(array('name' => 'duplicatetest'));

        $blog = $user->create_blog(array('title' => 'I AM TITLE', 'desc' => 'WHAT?'));
        $this->assertEquals($blog->id, $user->id);
        $this->assertEquals($blog->title, 'I AM TITLE');
        $this->assertEquals($blog->desc, 'WHAT?');

        try {
            $blog = $user->create_blog(array('title' => 'I AM TITLE', 'desc' => 'WHAT?'));
            $this->assertTrue(false);
        } catch (Pix_Table_DuplicateException $e) {
            $this->assertTrue(true);
        }
    }

    public function testSpecialChar()
    {
        $user = Pix_Table_Test_User::insert(array('name' => 'duplicatetest'));

        $user->password = "'";
        $user->save();
        $this->assertEquals(Pix_Table_Test_User::find($user->id)->password, "'");

        $user->password = "\"";
        $user->save();
        $this->assertEquals(Pix_Table_Test_User::find($user->id)->password, "\"");

        $user = Pix_Table_Test_User::insert(array('name' => 'duplicatetest', 'password' => '\''));
    }

    public function testSearch()
    {
        for ($i = 0; $i < 100; $i ++) {
            $user = Pix_Table_Test_User::insert(array('name' => $i));
        }

        $users = Pix_Table_Test_User::search(1);

        // limit 不要影響到後面
        $c = 0; foreach ($users as $u) { $c ++; }
        $this->assertEquals($c, 100);
        $c = 0; foreach ($users->limit(20) as $u) { $c ++; }
        $this->assertEquals($c, 20);
        $c = 0; foreach ($users as $u) { $c ++; }
        $this->assertEquals($c, 100);

        // order 不要影響後面
        $users = $users->order(array('name' => 'desc'));
        $this->assertEquals($users->first()->name, 99);
        $this->assertEquals($users->order('name asc')->first()->name, 0);
        $this->assertEquals($users->first()->name, 99);

        // search 不要影響後面
        $this->assertEquals($users->count(), 100);
        $this->assertEquals($users->search(array('name' => 50))->count(), 1);
        $this->assertEquals($users->count(), 100);

        // after, before 不要影響後面
        $users = $users->order(array('name' => 'desc'));
        $this->assertEquals($users->after($users->search(array('name' => '33'))->first())->first()->name, '32');
        $this->assertEquals($users->before($users->search(array('name' => '33'))->first())->first()->name, '34');
        $this->assertEquals($users->first()->name, 99);

        // max, min 不要影響後面
        $users = $users->order(array('name' => 'desc'));
        $this->assertEquals($users->max('name')->name, '99');
        $this->assertEquals($users->first()->name, 99);
        $users = $users->order(array('name' => 'asc'));
        $this->assertEquals($users->max('name')->name, '99');
        $this->assertEquals($users->first()->name, 0);

        $users = $users->order(array('name' => 'desc'));
        $this->assertEquals($users->min('name')->name, '0');
        $this->assertEquals($users->first()->name, 99);
        $users = $users->order(array('name' => 'asc'));
        $this->assertEquals($users->min('name')->name, '0');
        $this->assertEquals($users->first()->name, 0);
    }

    public function tearDown()
    {
        if (!extension_loaded('mysqli')) {
            return;
        }
	Pix_Table_Test_User::dropTable();
        Pix_Table_Test_Blog::dropTable();
	Pix_Table_Test_Article::dropTable();
    }
}
