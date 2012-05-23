<?php

require_once(__DIR__ . '/Table/Row.php');
require_once(__DIR__ . '/Table/ResultSet.php');

/**
 * Pix_Table 
 * 相關 Setting
 * Table:DropTableEnable 是否允許可以透過 Pix_Table dropTable, 預設 disable
 * 
 * @package Pix_Table
 * @version $id$
 * @copyright 2003-2010 PIXNET
 * @author Shang-Rung Wang <srwang@pixnet.tw> 
 * @license PHP Version 3.0 {@link http://www.php.net/license/3_0.txt}
 */
abstract class Pix_Table
{
    /**
     * init 第一次 load table 進來要做的事，替代掉 __contstruct
     *
     * @access public
     * @return void
     */
    public function init()
    {
    }

    /**
     * _columns 這個 Table 有哪些 column
     * 
     * @var array  key: column name, value: array('type' => 'int', 'unsigned' => true, 'size' => 10, 'default' => 3)
     * @access public
     */
    public $_columns = array();

    /**
     * _relations 這個 Table 有哪些 relation
     * 
     * @var array key: relation name, value: array('rel' => 'has_one|has_many', 'type' => 'Model', 'foreign_key' => 'column');
     * @access public
     */
    public $_relations = array(); 

    /**
     * _rowClass 這個 Table 的 Row class 
     * 
     * @var string
     * @access public
     */
    public $_rowClass = 'Pix_Table_Row';

    /**
     * _resultSetClass 這個 Table 的 ResultSet class
     * 
     * @var string
     * @access public
     */
    public $_resultSetClass = 'Pix_Table_ResultSet';

    /**
     * _name 這個 Table 在 Db 上面的名稱是什麼
     * 
     * @var string
     * @access public
     */
    public $_name = '';

    /**
     * _primary Primary key, 可以是
     * 
     * @var string|array
     * @access public
     */
    public $_primary;

    /**
     * _indexes 該 Table 用到的 index
     * 
     * @var array
     * @access public
     */
    public $_indexes;

    protected $_plugin_pools = array();
    protected $_plugins = array();
    protected $_plugin_class_options = array();
    protected $_filters = array();
    public static $_verify = true;
    public static $_save_memory = false;

    /**
     *  記錄哪些東西要 Cache
     */
    public static $_cache_groups = array();
    const CACHE_ALL = 0;
    const CACHE_FIND = 1;
    protected static $_cache;

    /**
     * 記錄哪些東西要 Log
     */
    public static $_log_groups = array();
    const LOG_CACHE = 0;
    const LOG_QUERY = 1;
    const LOG_ARRAYCACHE = 2;
    const LOG_SLOWQUERY = 3;
    public static $_force_master = false;

    protected $_link = null;

    public function __construct()
    {
    }

    protected static $_query_comment = null;

    /**
     * setQueryComment 在 Db 下 Query 時，自動加上註解
     * 
     * @param null/string $comment 
     * @static
     * @access public
     * @return void
     */
    public static function setQueryComment($comment = null)
    {
	self::$_query_comment = $comment;
    }

    /**
     * getQueryComment 取得要在 db 下 query 時所加上的註解
     * 
     * @static
     * @access public
     * @return null|string
     */
    public static function getQueryComment()
    {
	return self::$_query_comment;
    }

    /**
     * setLongQueryTime 當 Query 時間超過這個時間時，會噴出 Warning log
     * 
     * @param int $second 
     * @static
     * @access public
     * @return void
     */
    public static function setLongQueryTime($second = 1)
    {
	self::$_log_groups[self::LOG_SLOWQUERY] = $second;
    }

    /**
     * getLongQueryTime 取得 Slow query 的設定秒數
     * 
     * @static
     * @access public
     * @return 
     */
    public static function getLongQueryTime()
    {
	return self::$_log_groups[self::LOG_SLOWQUERY];
    }

    public function addFilter($filter, $funcname = '')
    {
	if (!$funcname = ($funcname)) {
	    $funcname = 'filter_' . $filter;
	}
	$this->_filters[$filter] = $funcname;
    }

    public function getFilters()
    {
	return $this->_filters;
    }

    /**
     * setForceMaster 設定要不要強制從 master 抓資料。
     *
     * @param mixed $enable
     * @static
     * @access public
     * @return void
     */
    static public function setForceMaster($enable)
    {
        self::$_force_master = intval($enable);
    }

    /**
     * getForceMaster 取得是否要強制從 master 抓資料的設定
     *
     * @static
     * @access public
     * @return boolean
     */
    static public function getForceMaster()
    {
        return self::$_force_master;
    }

    public static function setCache($cache)
    {
	if (!is_null($cache) and !($cache instanceof Pix_Cache)) {
	    throw new Exception('不正確的 Cache');
	}
	self::$_cache = $cache;
    }

    public static function getCache()
    {
	return self::$_cache;
    }

    /**
     * enableLog 啟用某個 LOG
     *
     * @param mixed $group
     * @static
     * @access public
     * @return void
     */
    public static function enableLog($group)
    {
        self::$_log_groups[$group] = true;
    }

    /**
     * disableLog 停用某個 LOG
     *
     * @param mixed $group
     * @static
     * @access public
     * @return void
     */
    public static function disableLog($group)
    {
        unset(self::$_log_groups[$group]);
    }

    /**
     * getLogStatus 取得某個 log 的狀態
     *
     * @param mixed $group
     * @static
     * @access public
     * @return void
     */
    static public function getLogStatus($group)
    {
        return self::$_log_groups[$group];
    }

    /**
     * enableCache 開啟 Pix_Table 的 cache 功能
     *
     * @param mixed $group
     * @static
     * @access public
     * @return void
     */
    static public function enableCache($group)
    {
        self::$_cache_groups[$group] = true;
    }

    /**
     * disableCache 關閉 Pix_Table 的 cache 功能
     *
     * @param mixed $group
     * @static
     * @access public
     * @return void
     */
    static public function disableCache($group)
    {
        unset(self::$_cache_groups[$group]);
    }

    /**
     * getCacheStatus 取得某個 group 的 cache 功能是開是關
     *
     * @param string $group
     * @static
     * @access public
     * @return void
     */
    static public function getCacheStatus($group)
    {
        return self::$_cache_groups[$group];
    }

    // @codeCoverageIgnoreStart
    public static function debug($word)
    {
	error_log($word, 0);
    }
    // @codeCoverageIgnoreEnd

    /**
      * 用 Table 的 link 做 query 的函式
      * @deprecated 不要再使用這個了
      * @codeCoverageIgnoreStart
      * @param  string $sql  SQL 指令
      * @param  string $type  'master' or 'slave'
      * @return Mysqli_Result mysql query 結果
      **/
    public function query($sql, $type = 'master')
    {
        $link = $this->getDb();
        $res = $link->query($sql);

        return $res;
    }
    // @codeCoverageIgnoreEnd

    static protected $_default_db = null;
    /**
     * setDefaultDb 指定 Pix_Table 預設的 db
     *
     * @param Pix_Table_Db $db 用哪個 db
     * @static
     * @access public
     * @return void
     */
    static public function setDefaultDb($db)
    {
        if (is_null($db)) {
            self::$_default_db = null;
        } else {
            $db = Pix_Table_Db::factory($db);
            self::$_default_db = $db;
        }
    }

    /**
     * getDefaultDb 取得預設的 Db
     *
     * @static
     * @access public
     * @return void
     */
    static public function getDefaultDb()
    {
        return self::$_default_db;
    }

    /**
     * getLink 取得這個 Table 專用的 Db，給下面的 Table 繼承用的
     *
     * @access public
     * @return Pix_Table_Db
     */
    public function getLink() {
        return Pix_Table::getDefaultDb();
    }

    /**
      * 取得該 Table 專用的 Mysqli Link 
      * XXX: 這個函式請不要直接在外面呼叫，這是給 Library 內部自己用的
      * @return Mysqli Pix_Table_Db link
      **/
    public function getDb()
    { 
        if ($this->_link) {
	    return $this->_link;
        }

        if (!$link = $this->getLink($type)) {
            throw new Exception("你必需實作 " . $this->getClass() . "::getLink(\$type); (type = $type)");
        }
        $this->_link = Pix_Table_Db::factory($link);

        return $this->_link;
    }

    static public function setDb($link)
    {
        $table = self::getTable($this);
        $table->_link = $link;
    }

    protected static $_table_pool = array();

    /**
     * is_a $object 是否是 $table 類型的 Row
     *
     * @param Pix_Table_Row $object
     * @param string $table
     * @static
     * @access public
     * @return boolean
     */
    public static function is_a($object, $table)
    {
	if (!($object instanceof Pix_Table_Row)) {
	    return false;
	}
	return $object->getTableClass() == $table;
    }

    public static function getTable($table = null)
    {
	//	require_once($table . '.php');
	if (!is_null($table)) {
	    if (!is_scalar($table) and is_a($table, 'Pix_Table')) {
		return $table;
	    }
	    if (!isset(self::$_table_pool[$table])) {
		if (!is_scalar($table) or !class_exists($table)) {
		    throw new Pix_Table_Exception("找不到 {$table} 這個 class ，請確認是否有 link 到 classes 或者是有打錯字");
		}
		self::$_table_pool[$table] = true;
		self::$_table_pool[$table] = $t = new $table();
		$t->init();
	    }

	    if (true === self::$_table_pool[$table]) {
		throw new Pix_Table_Exception("在 __contstruct 內呼叫 static function 會炸，請將動作搬到 init 內 ");
	    }

	    return self::$_table_pool[$table];
	}

	if (function_exists('get_called_class')) {
	    $tableName = get_called_class();
	    return self::getTable($tableName);
	}

        // @codeCoverageIgnoreStart
	throw new Exception('PHP 5.3.0 以上才支援這功能喔');
        // @codeCoverageIgnoreEnd
    }

    /**
     * createTable 在資料庫上建立新 Table
     * 
     * @static
     * @access public
     * @return void
     */
    static public function createTable()
    {
	$table = self::getTable($this);
        return $table->getDb()->createTable($table);
    }

    /**
     * checkTable 檢查 Table 與資料庫上的不同
     *
     * @static
     * @access public
     * @return array 不同點
     */
    static public function checkTable()
    {
	$table = self::getTable($this);
	return $table->getDb()->checkTable($table);
    }

    /**
     * dropTable 刪除這個 table
     * 
     * @static
     * @access public
     * @return void
     */
    static public function dropTable()
    {
        if (!Pix_Setting::get('Table:DropTableEnable')) {
            throw new Pix_Table_Exception("要 DROP TABLE 前請加上 Pix_Setting::set('Table:DropTableEnable', true);");
        }
	$table = self::getTable($this);
	return $table->getDb()->dropTable($table);
    }

    /**
     * search 搜尋特定條件
     * 
     * @param mixed $where 
     * @static
     * @access public
     * @return Pix_Table_ResultSet
     */
    static public function search($where)
    {
	$table = self::getTable($this);
	$conf = array();
	$conf['tableClass'] = $table->getClass();
	$conf['where'] = $where;

	$resultSetClass = $table->_resultSetClass;
	return new $resultSetClass($conf);
    }
	
    public $_cache_rows = array();

    /**
     * find 透過 $primary_value 這個 pk 取得一筆 row
     * 
     * @param mixed $primary_value 
     * @static
     * @access public
     * @return Pix_Table_Row|null
     */
    static public function find($primary_value) 
    { 
	$table = self::getTable();

	if (is_scalar($primary_value)) {
	    $primary_value = array($primary_value);
	}

	if (false !== ($row = $table->getRowFromCache($primary_value))) {
	    return $row;
	}

	$conf = array();
	$conf['tableClass'] = $table->getClass();
	$conf['primary_value'] = $primary_value;

        if (!$row = $table->getDb()->fetchOne($table, $primary_value)) {
	    $table->cacheRow($primary_value, null);
	    return null;
	}
	$conf['data'] = $row;
	$table->cacheRow($primary_value, $row);

	$rowClass = $table->_rowClass;
	$row = new $rowClass($conf);

	return $row;
    }

    static public function find_by($columns, $values)
    {
	$table = self::getTable();

	return $table->search(array_combine($columns, $values))->first();
    }

    /**
     * createRow 新增加一個未被存入資料庫內的 row
     * 
     * @param Pix_Table_Row|null $belong_row 是從哪個 Row 被 create 出來的，Pix_Table 不會用到，但是先預留
     * @static
     * @access public
     * @return Pix_Table_Row
     */
    static public function createRow($belong_row = null)
    {
	$table = self::getTable();

	$conf['tableClass'] = $table->getClass();
	foreach ($table->_columns as $name => $param) {
	    if (isset($param['default'])) {
		$conf['default'][$name] = $param['default'];
	    }
	}
	$rowClass = $table->_rowClass;
	return new $rowClass($conf);
    }

    /**
     * insert 新增一筆資料進資料庫(立刻會存進資料庫)
     * 
     * @param mixed $data 
     * @static
     * @access public
     * @return void
     */
    static public function insert($data)
    {
	$table = self::getTable();
	$row = $table->createRow();
	foreach ($data as $column => $value) {
	    if (isset($table->_columns[$column]) or isset($table->_relations[$column]) or ('_' == $column[0]) or isset($table->_hooks[$column]['set'])) {
		$row->{$column} = $value;
	    }
	}

	$row->save();
	return $row;
    }

    public function getPrimaryColumns()
    {
	if (!is_array($this->_primary)) {
	    $this->_primary = array($this->_primary);
	}
	return $this->_primary;
    }

    /**
     * isNumbericColumn 回傳現在這個 column 是不是只有數字
     *
     * @param string $column column name
     * @access public
     * @return boolean
     */
    public function isNumbericColumn($column)
    {
	if (is_scalar($column) and isset($this->_columns[$column]['type']) and in_array($this->_columns[$column]['type'], array('int', 'tinyint'))) {
	    return true;
	}
	return false;
    }

    static protected $_static_result_set_plugins = array();

    static public function addResultSetStaticPlugins($plugin, $funcs = array())
    {
	if (@class_exists('Pix_Table_ResultSet_Plugin_' . ucfirst($plugin))) {
	    $plugin = 'Pix_Table_ResultSet_Plugin_' . ucfirst($plugin);
	}

	if (!class_exists($plugin)) {
	    throw new Pix_Table_Exception("Plugin {" . $plugin . "} is not found");
	}

	if (!$funcs) {
	    $funcs = call_user_func(array($plugin, 'getFuncs'));
	}

	foreach ($funcs as $func) {
	    self::$_static_result_set_plugins[strtolower($func)] = $plugin;
	}
    }

    public function getResultSetPlugin($func)
    {
	if ($plugin = self::$_static_result_set_plugins[strtolower($func)]) {
	    return $plugin;
	}
	return null;
    }

    public function addPlugins($methods, $class, $options = array())
    {
	if (!is_array($methods)) {
	    $methods = array($methods);
	}

	foreach ($methods as $call_method => $plugin_method) {
	    $conf = array('class' => $class);
	    if (!preg_match('#^[0-9]*$#', $call_method)) {
		$conf['plugin_method'] = $plugin_method;
	    } else {
		$call_method = $plugin_method;
	    }
	    $this->_plugins[$call_method] = $conf;
	}

	$this->_plugin_class_options[$class] = $options;
    }

    public function getPluginMap($method)
    {
        $plugins = $this->getPlugins();
        return $plugins[$method]['plugin_method'] ? $plugins[$method]['plugin_method'] : $method;
    }

    public function getPlugin($method)
    {
        $plugins = $this->getPlugins();
        if (!$plugin = $plugins[$method]) {
            return null;
        }
	$class = $plugin['class'];
	if (!$plugin = $this->_plugin_pools[$class]) {
	    if (!class_exists($class)) {
		throw new Pix_Table_Exception("在取得 " . $this->getClass() . " 的 {$method} plugins 時，找不到 {$class} class");
	    }
	    $this->_plugin_pools[$class] = $plugin = new $class($this->_plugin_class_options[$class]);
	    if (!($plugin instanceof Pix_Table_Plugin)) {
		throw new Pix_Table_Exception("{$class} 不是 Pix_Table_Plugin");
	    }
	}
	return $plugin;
    }

    static public function __callStatic($name, $args)
    {
	$table = self::getTable($this);
	return $table->__call($name, $args);
    }

    public function __call($name, $args)
    {
	if (preg_match('#find_by_(.+)#', $name, $ret)) {
	    $column = explode('_and_', $ret[1]);
	    return $this->find_by($column, $args);
	}
	throw new Pix_Table_Exception("找不到這個函式喔: {$name}");
    }

    /**
     * getPlugins 回傳目前這個 Model 所有的 Plugins
     * 
     * @static
     * @access public
     * @return array
     */
    static public function getPlugins()
    {
	$table = self::getTable($this);
	return $table->_plugins;
    }

    protected $_table_cache = false;
    protected $_table_cache_prefix = '';

    /**
     * enableTableCache 啟用 Table Cache
     *
     * @access public
     * @return void
     */
    public function enableTableCache($cache_prefix = '')
    {
	$this->_table_cache_prefix = $cache_prefix;
	$this->_table_cache = true;
    }

    /**
     * disableTableCache 停用 Table Cache
     *
     * @access public
     * @return void
     */
    public function disableTableCache()
    {
	$this->_table_cache = false;
    }

    /**
     * getRowFromCache 從 Cache 中取得 Row
     *
     * @param array $primary_values Primary values
     * @access public
     * @return false - 沒有 cache 到或不支援 cache, null - cache 到 null, Pix_Table_Row cache 到的 row
     */
    public function getRowFromCache($primary_values)
    {
	if (is_scalar($primary_values)) {
	    $primary_values = array($primary_values);
	}

	if ( count($primary_values) == 1 and isset($this->_cache_rows[$primary_values[0]])) {
	    $data = $this->_cache_rows[$primary_values[0]];
	}

	if (is_null($data)) {
	    if (!$this->_table_cache) return false;
	    if (!$cache = $this->getCache()) return false;

	    $table_class = $this->getClass();
	    $cache_key = "Pix_Table_Cache:{$table_class}:{$this->_table_cache_prefix}:" . implode('-', $primary_values);

	    $data = $cache->load($cache_key);
	    if (is_null($data)) {
		return null;
            }
            if (false === $data) {
		return false;
	    }
            $data = unserialize($data);
            $data = $data['data'];
	}

	if (false === $data) {
	    return false;
	}

	$conf = array();
	$conf['tableClass'] = $this->getClass();
	$conf['primary_value'] = $primary_values;
	$conf['data'] = $data;

	$rowClass = $this->_rowClass;
	return new $rowClass($conf);
    }

    /**
     * cacheRow
     *
     * @param array $primary_values Primary values
     * @param array $data
     * @access public
     * @return void
     */
    public function cacheRow($primary_values, $data)
    {
	if (is_scalar($primary_values)) {
	    $primary_values = array($primary_values);
	}

	// memory cache
	if (!self::$_save_memory) {
	    if (count($primary_values) == 1) {
		$this->_cache_rows[$primary_values[0]] = $data;
	    }
	}

	// table cache
	if (!$this->_table_cache) return false;
	if (!$cache = $this->getCache()) return false;

	$table_class = $this->getClass();
	$cache_key = "Pix_Table_Cache:{$table_class}:{$this->_table_cache_prefix}:" . implode('-', $primary_values);

	if (is_null($data)) {
	    $cache->save($cache_key, null);
	    return;
	}

	if (false === $data) {
	    $cache->delete($cache_key);
	    return;
	}

        $json = serialize(array('data' => $data));
        $cache->save($cache_key, $json);
    }

    protected $_class = null;

    /**
     * getClass 回傳這個 table 的 class name
     *
     * @access public
     * @return void
     */
    public function getClass()
    {
        return get_class($this);
    }

    protected $_index_datas = array();

    /**
     * addIndex 增加一組 INDEX
     *
     * @param string $name 增加 INDEX 名稱
     * @param array $columns 哪個 column
     * @param string $type index|unique
     * @static
     * @access public
     * @return void
     */
    static public function addIndex($name, $columns, $type = 'index')
    {
	$table = self::getTable($this);
	$table->_index_datas[$name] = array('columns' => $columns, 'type' => $type);
    }

    static public function getIndexColumns($name)
    {
	$table = self::getTable($this);
	if ('PRIMARY' == $name) {
	    return $table->getPrimaryColumns();
	}
	return $table->_index_datas[$name]['columns'];
    }

    /**
     * getRelationForeignTable 取得 relaton 對應的 table
     *
     * @param string $relation relation 名稱
     * @static
     * @access public
     * @return Pix_Table 對應的 table
     */
    static public function getRelationForeignTable($relation)
    {
        $table = self::getTable($this);

        if (!$relation_data = $table->_relations[$relation]) {
            throw new Pix_Table_Exception("{$table->getClass()} 找不到 {$relation} 這個 relation");
        }

        if (!$table_name = $relation_data['type']) {
            throw new Pix_Table_Exception("{$table->getClass()}->{$relation} 沒有指定 Table Type");
        }

        return Pix_Table::getTable($table_name);
    }

    /**
     * getRelationForeignKeys 取得某個 relation 的 relation name
     *
     * @param string $relation relation 名稱
     * @static
     * @access public
     * @return array relation 的 columns
     */
    static public function getRelationForeignKeys($relation)
    {
        $table = self::getTable($this);
        if (!$relation_data = $table->_relations[$relation]) {
            throw new Pix_Table_Exception("{$table->getClass()} 找不到 {$relation} 這個 relation");
        }

        // 有指定 foreign key 就直接回傳
        if ($keys = $relation_data['foreign_key']) {
            return is_array($keys) ? $keys : array($keys);
        }

        // 沒指定的話， has_one 預設用 B 的 PK
        if (in_array($relation_data['rel'], array('has_one', 'belongs_to'))) {
            return $table->getRelationForeignTable($relation)->getPrimaryColumns();
        } elseif ('has_many' == $relation_data['rel']) { // has_many 的話，預設用 A 的 PK
            return $table->getPrimaryColumns();
        }

        throw new Pix_Table_Exception("{$table->getClass()}->{$relation} 未指定 foreign_key");
    }

    /**
     * findUniqueKey 尋找這要用哪一個 Unique Key
     *
     * @param array $columns 有哪些 column
     * @static
     * @access public
     * @return string|null 哪一組 index
     */
    static public function findUniqueKey($columns)
    {
	$table = self::getTable($this);
	$primary_columns = $table->getPrimaryColumns();
	if (array_intersect($primary_columns, $columns) == $primary_columns) {
	    return 'PRIMARY';
	}

	foreach ($table->_index_datas as $name => $data) {
	    if ('unique' != $data['type']) {
		continue;
	    }

	    if (!array_diff($data['columns'], $columns)) {
		return $name;
	    }
	}

	return null;
    }

    /**
     * getTableName 取得 Table 名稱
     *
     * @static
     * @access public
     * @return string
     */
    static public function getTableName()
    {
	$table = self::getTable($this);
        return $table->_name;
    }

    /**
     * isEditableKey  是否是可以被修改的 Row Key, Ex: column, relation ...
     *
     * @param string $key
     * @static
     * @access public
     * @return boolean
     */
    static public function isEditableKey($key)
    {
        $table = self::getTable();
        if ($table->_columns[$key]) {
            return true;
        }

        if ($table->_relations[$key]) {
            return true;
        }

        if ($table->_hooks[$key]['set']) {
            return true;
        }

        if ('_' == $key[0]) {
            return true;
        }

        return false;
    }
}
