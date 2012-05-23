<?php

require_once(dirname(__FILE__) . '/../../init.inc.php');
Pix_Table::$_save_memory = true;
class Pix_Table_TestCluster_User extends Pix_Table_Cluster
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

        $this->_relations['blog'] = array('rel' => 'has_one', 'type' => 'Pix_Table_TestCluster_Blog');

        $this->_mappings['blog'] = array('table' => 'mapping_blog', 'default_cluster' => 1, 'auto_create' => true, 'clusters' => array(0, 1));
    }

    static public function changeCluster($idx)
    {
        $table = self::getTable($this);
        $table->_mappings['blog']['default_cluster'] = $idx;
    }

    public function getMappingLink($mapping, $type)
    {
        return Pix_Test::getDbAdapter();
    }

    public function getClusterLink($mapping, $type, $idx)
    {
        if ('blog' == $mapping) {
            return Pix_Test::getDbAdapter($idx);
        }
    }
}

class Pix_Table_TestCluster_Blog extends Pix_Table_Cluster
{
    public function __construct()
    {
        $this->_name = 'blog';

        $this->_primary = 'id';

	$this->_columns['id'] = array('type' => 'int', 'auto_increment' => true);
        $this->_columns['title'] = array('type' => 'varchar', 'size' => 32, 'default' => '');
        $this->_columns['desc'] = array('type' => 'varchar', 'size' => 32, 'default' => '');

        $this->_relations['user'] = array('rel' => 'has_one', 'type' => 'Pix_Table_TestCluster_User');
        $this->_relations['info'] = array('rel' => 'has_one', 'type' => 'Pix_Table_TestCluster_BlogInfo');

        $this->_mappings['default'] = array('belong' => 'user', 'mapping' => 'blog');
    }
}

class Pix_Table_TestCluster_BlogArticle extends Pix_Table_Cluster
{
    public function __construct()
    {
        $this->_name = 'article';

        $this->_primary = 'id';

	$this->_columns['id'] = array('type' => 'int', 'auto_increment' => true);
        $this->_columns['blog_id'] = array('type' => 'int');
        $this->_columns['title'] = array('type' => 'varchar', 'size' => 32, 'default' => '');
        $this->_columns['desc'] = array('type' => 'varchar', 'size' => 32, 'default' => '');

        $this->_relations['blog'] = array('rel' => 'has_one', 'type' => 'Pix_Table_TestCluster_Blog', 'foreign_key' => 'blog_id');

        $this->_mappings['default'] = array('belong' => 'blog', 'mapping' => 'default');
    }
}

class Pix_Table_TestCluster_BlogInfo extends Pix_Table_Cluster
{
    public function __construct()
    {
        $this->_name = 'bloginfo';

        $this->_primary = 'id';

	$this->_columns['id'] = array('type' => 'int', 'auto_increment' => true);
        $this->_columns['info'] = array('type' => 'varchar', 'size' => 32, 'default' => '');

        $this->_relations['blog'] = array('rel' => 'has_one', 'type' => 'Pix_Table_TestCluster_Blog');

        $this->_mappings['default'] = array('belong' => 'blog');
    }
}

class Pix_Table_TestCluster extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Pix_Table_TestCluster_User::createTable();
        Pix_Table_TestCluster_Blog::createTable();
        Pix_Table_TestCluster_BlogArticle::createTable();
        Pix_Table_TestCluster_BlogInfo::createTable();
        Pix_Table_TestCluster_User::getMappingTable('blog')->createTable();
    }

    public function test()
    {
	$row = Pix_Table_TestCluster_User::insert(array('name' => 'testtest'));
	$this->assertEquals($row->getTableClass(), 'Pix_Table_TestCluster_User');
	$this->assertEquals($row->name, 'testtest');
	$this->assertEquals(Pix_Table_TestCluster_User::search(1)->count(), 1);

	$id = $row->id;
	$row = Pix_Table_TestCluster_User::find($id);
	$this->assertEquals($row->getTableClass(), 'Pix_Table_TestCluster_User');
	$this->assertEquals($row->name, 'testtest');

	$rows = Pix_Table_TestCluster_User::search(array('name' => 'testtest'));
	$this->assertEquals($rows->count(), 1);

	$row = $rows->first();
	$this->assertEquals($row->getTableClass(), 'Pix_Table_TestCluster_User');
	$this->assertEquals($row->name, 'testtest');

	$rows = Pix_Table_TestCluster_User::search(array('name' => 'notfound'));
	$this->assertEquals($rows->count(), 0);
	$this->assertEquals(null, $rows->first());

	$row = Pix_Table_TestCluster_User::createRow();
	$row->name = 'test2';
	$this->assertEquals($row->id, null);
	$row->save();

	$this->assertEquals(Pix_Table_TestCluster_User::search(1)->count(), 2);
    }

    public function testCreate()
    {
        Pix_Table_TestCluster_User::changeCluster(0);
        $user1 = Pix_Table_TestCluster_User::insert(array('name' => 'test1'));
        $blog = $user1->create_blog(array('title' => 'I AM TITLE', 'desc' => 'WHAT?'));
        $this->assertEquals($blog->id, $user1->id);
        $this->assertEquals($blog->title, 'I AM TITLE');
        $this->assertEquals($blog->desc, 'WHAT?');

        $info = $blog->create_info(array('info' => 'info~~~'));
        $this->assertEquals($info->info, 'info~~~');

        Pix_Table_TestCluster_User::changeCluster(1);
        $user2 = Pix_Table_TestCluster_User::insert(array('name' => 'test2'));
        $blog = $user2->create_blog(array('title' => 'I AM TITLE2', 'desc' => 'WHAT?2'));
        $this->assertEquals($blog->id, $user2->id);
        $this->assertEquals($blog->title, 'I AM TITLE2');
        $this->assertEquals($blog->desc, 'WHAT?2');

        $this->assertEquals(Pix_Table_TestCluster_Blog::search(1, 0)->count(), 1);
        $this->assertEquals(Pix_Table_TestCluster_Blog::search(1, 1)->count(), 1);

        $this->assertEquals($user1->blog->title, 'I AM TITLE');
        $this->assertEquals($user2->blog->title, 'I AM TITLE2');
    }

    public function testResultSet()
    {
        // 沒 cluster
        $this->assertEquals(Pix_Table_TestCluster_User::search(1)->count(), 0);
        $this->assertEquals(Pix_Table_TestCluster_User::search(1)->sum('id'), 0);
        $user1 = Pix_Table_TestCluster_User::insert(array('name' => 'user1'));
        $user2 = Pix_Table_TestCluster_User::insert(array('name' => 'user2'));
        $user3 = Pix_Table_TestCluster_User::insert(array('name' => 'user3'));
        $this->assertEquals(Pix_Table_TestCluster_User::search(1)->count(), 3);
        $ids = array(1,2,3);
        $this->assertEquals(Pix_Table_TestCluster_User::search(1)->sum('id'), array_sum($ids)); // 1 + 2 + 3
        $this->assertEquals(array_values(Pix_Table_TestCluster_User::search(1)->toArray('id')), $ids);
        foreach (Pix_Table_TestCluster_User::search(1) as $user) {
            $this->assertEquals($user->id, array_shift($ids));
        }
        $user4 = Pix_Table_TestCluster_User::insert(array('name' => 'user4'));


        // 用指定 cluster 的
        $this->assertEquals(Pix_Table_TestCluster_Blog::search(1, 0)->count(), 0);
        $this->assertEquals(Pix_Table_TestCluster_Blog::search(1, 0)->sum('id'), 0);
        $this->assertEquals(Pix_Table_TestCluster_Blog::search(1, 1)->count(), 0);
        $this->assertEquals(Pix_Table_TestCluster_Blog::search(1, 1)->sum('id'), 0);
        Pix_Table_TestCluster_User::changeCluster(0);
        $this->assertEquals(Pix_Table_TestCluster_Blog::search(1, $user1)->count(), 0);
        $this->assertEquals(Pix_Table_TestCluster_Blog::search(1, $user1)->sum('id'), 0);
        $blog1 = $user1->create_blog(array('title' => 'blog1'));
        $blog2 = $user2->create_blog(array('title' => 'blog2'));
        Pix_Table_TestCluster_User::changeCluster(1);
        $this->assertEquals(Pix_Table_TestCluster_Blog::search(1, $user3)->count(), 0);
        $this->assertEquals(Pix_Table_TestCluster_Blog::search(1, $user3)->sum('id'), 0);
        $blog3 = $user3->create_blog(array('title' => 'blog3'));
        $blog4 = $user4->create_blog(array('title' => 'blog4'));

        $ids = array(1,2);
        $this->assertEquals(Pix_Table_TestCluster_Blog::search(1, 0)->count(), count($ids));
        $this->assertEquals(Pix_Table_TestCluster_Blog::search(1, 0)->sum('id'), array_sum($ids)); // 1 + 2
        $this->assertEquals(array_values(Pix_Table_TestCluster_Blog::search(1, 0)->toArray('id')), $ids);
        foreach (Pix_Table_TestCluster_Blog::search(1, 0) as $blog) {
            $this->assertEquals($blog->id, array_shift($ids));
        }
        $ids = array(3, 4);
        $this->assertEquals(Pix_Table_TestCluster_Blog::search(1, 1)->count(), count($ids));
        $this->assertEquals(Pix_Table_TestCluster_Blog::search(1, 1)->sum('id'), array_sum($ids)); // 3 + 4
        $this->assertEquals(array_values(Pix_Table_TestCluster_Blog::search(1, 1)->toArray('id')), $ids);
        foreach (Pix_Table_TestCluster_Blog::search(1, 1) as $blog) {
            $this->assertEquals($blog->id, array_shift($ids));
        }


        // belong 有 cluster 的

        // belong 沒有 cluster 的
    }

    public function tearDown()
    {
	Pix_Table_TestCluster_User::dropTable();
        Pix_Table_TestCluster_Blog::dropTable();
        Pix_Table_TestCluster_BlogArticle::dropTable();
        Pix_Table_TestCluster_BlogInfo::dropTable();
        Pix_Table_TestCluster_User::getMappingTable('blog')->dropTable();
    }
}
