<?php
/**
 *  mysqli_get_warnings() 之後， mysqli->insert_id 會抓不到東西，造成 create_rows 回傳的 row pk 不正確的 bug
 */

require_once(dirname(__FILE__) . '/../../init.inc.php');

class Pix_Table_Test13379_User extends Table
{
    static protected $link;

    public function getLink($type)
    {
        return Pix_Table_Test13379::getLink($type);
    }

    public function __construct()
    {
	$this->_name = 'user';
	$this->_primary = 'id';

	$this->_columns['id'] = array('type' => 'int', 'auto_increment' => true);
        $this->_columns['no'] = array('type' => 'varchar', 'size' => 3);
    }
}

class Pix_Table_Test13379_UserEAV extends Table
{
    public function getLink($type)
    {
        return Pix_Table_Test13379::getLink($type);
    }

    public function __construct()
    {
	$this->_name = 'usereav';
	$this->_primary = array('id', 'key');

        $this->_columns['id'] = array('type' => 'int');
        $this->_columns['key'] = array('type' => 'varchar', 'size' => 3);
        $this->_columns['value'] = array('type' => 'varchar', 'size' => 3);
    }
}

class Pix_Table_Test13379 extends PHPUnit_Framework_TestCase
{
    public function getLink($type)
    {
        return Pix_Test::getDbAdapter();
    }
    public function setUp()
    {
        // sql_mode 如果是 STRICT_ALL_TABLES 會讓 warning 變 error...
        if (in_array(PIXCORE_TEST_DB_CORE, array('mysql', 'mysqlconf'))) {
            Pix_Table_Test13379::getLink('master')->query("set sql_mode=''");
        }
        Pix_Table_Test13379_User::createTable();
        Pix_Table_Test13379_UserEAV::createTable();
    }

    public function test()
    {
	// XXX: 本來想測試會不會跳出 warning 的，但是 PHPUnit 抓不到 E_USER_WARNING ，所以這邊要加 @
        $user_1 = @Pix_Table_Test13379_User::insert(array('no' => '12345678900'));
        $this->assertEquals($user_1->id, 1);

        Pix_Table_Test13379_UserEAV::insert(array('id' => $user_1->id, 'key' => 'a', 'value' => 'b'));
    }

    public function tearDown()
    {
	Pix_Table_Test13379_User::dropTable();
        Pix_Table_Test13379_UserEAV::dropTable();
    }
}
