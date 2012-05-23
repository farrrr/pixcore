<?php
define('THRIFT_ROOT', '/net/account/pixuser/gasol/work/thrift-php');
$GLOBALS['THRIFT_ROOT'] = THRIFT_ROOT;

set_include_path(
    dirname(__FILE__) . '/..' . PATH_SEPARATOR . 
    dirname(__FILE__) . '/.' . PATH_SEPARATOR . 
    get_include_path()
);
require('Pix/Loader.php');
Pix_Loader::registerAutoload();

$baseDir = dirname(__FILE__);
if (file_exists("$baseDir/debug.php")) {
    require("$baseDir/debug.php");
}

define('PIXCORE_TEST_DB_CORE', 'mysql');

echo "Pix_Table_Core: " . PIXCORE_TEST_DB_CORE . PHP_EOL;
if (defined('PIXCORE_TEST_TABLE_CLUSTER') and PIXCORE_TEST_TABLE_CLUSTER) {
    class Table extends Pix_Table_Cluster{};
    class Table_Row extends Pix_Table_Cluster_Row{};
    echo "Pix_Table_Cluster: true\n";
} else {
    class Table extends Pix_Table{};
    class Table_Row extends Pix_Table_Row{};
    echo "Pix_Table_Cluster: false\n";
}

class Pix_Test
{
    static protected $_db_links = array();
    static protected $_db_core = PIXCORE_TEST_DB_CORE;

    static public function getDbAdapter($name = 'default')
    {
        $link = self::$_db_links[$name];
        if (!$link or ('mysql' == self::$_db_core and !$link->ping())) {
            if ('sqlite' == self::$_db_core) {
                self::$_db_links[$name] = new Pix_Table_Db_Adapter_Sqlite(':memory:');
                self::$_db_links[$name]->setName($name);
            } elseif ('mysql' == self::$_db_core) {
                $link = new mysqli(PIXCORE_TEST_MYSQLHOST, PIXCORE_TEST_MYSQLUSER, PIXCORE_TEST_MYSQLPASS);
                if ('default' == $name) {
                    $link->select_db(PIXCORE_TEST_MYSQLDB);
                } elseif (0 == $name) {
                    $link->select_db(PIXCORE_TEST_MYSQLDB_0);
                } elseif (1 == $name) {
                    $link->select_db(PIXCORE_TEST_MYSQLDB_1);
                }
                self::$_db_links[$name] = $link;
            } elseif ('mysqlconf' == self::$_db_core) {
                $obj = new StdClass;
                $obj->master->host = PIXCORE_TEST_MYSQLHOST;
                $obj->master->username = PIXCORE_TEST_MYSQLUSER;
                $obj->master->password = PIXCORE_TEST_MYSQLPASS;
                $obj->slave->host = PIXCORE_TEST_MYSQLHOST;
                $obj->slave->username = PIXCORE_TEST_MYSQLUSER;
                $obj->slave->password = PIXCORE_TEST_MYSQLPASS;

                if ('default' == $name) {
                    $obj->master->dbname = $obj->slave->dbname = PIXCORE_TEST_MYSQLDB;
                } elseif (0 == $name) {
                    $obj->master->dbname = $obj->slave->dbname = PIXCORE_TEST_MYSQLDB_0;
                } elseif (1 == $name) {
                    $obj->master->dbname = $obj->slave->dbname = PIXCORE_TEST_MYSQLDB_1;
                }
                self::$_db_links[$name] = new Pix_Table_Db_Adapter_MysqlConf(array($obj));
            }
        }
        return self::$_db_links[$name];
    }

    static public function setDbAdapter($core)
    {
        self::$_db_core = $core;
        self::$_db_links = array();
    }
}
