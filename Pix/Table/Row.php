<?php

/**
 * Pix_Table_Row 
 * 
 * @package Pix_Table
 * @version $id$
 * @copyright 2003-2010 PIXNET
 * @author Shang-Rung Wang <srwang@pixnet.tw> 
 * @license PHP Version 3.0 {@link http://www.php.net/license/3_0.txt}
 */
class Pix_Table_Row
{
    protected $_tableClass;
    protected $_primary_value = null;

    protected $_data = array();
    protected $_orig_data = array();
    protected $_user_data = array();

    protected $_relation_data = array();

    public function __destruct()
    {
	unset($this->_data);
	unset($this->_orig_data);
    }

    public function equals($row)
    {
	if ($row instanceof Pix_Table_Row) {
	    if ($row->_tableClass != $this->_tableClass) {
		throw new Pix_Table_Exception('這兩個是不同的 ModelRow ，你的程式是不是寫錯了?');
	    }
	    $primary_b = $row->getPrimaryValues();
	} elseif (is_scalar($row)) {
	    $primary_b = array($row);
	} elseif (is_array($row)) {
	    $primary_b = $row;
	}

	$primary_a = $this->getPrimaryValues();

	return $primary_a == $primary_b;
    }

    public function updateByString($args)
    {
        try {
            $this->preSave();
            $this->preUpdate(array());
        } catch (Pix_Table_Row_Stop $e) {
            return;
        }
        $this->getRowDb()->updateOne($this, $args);

        $this->refreshRowData();

        $this->postUpdate(array());
        $this->cacheRow($this->_data);
        $this->postSave();
    }

    public function update($args)
    {
        if (!is_array($args)) {
            return $this->updateByString($args);
	}
	$table = $this->getTable();

        foreach ($args as $column => $value) {
            if ($table->isEditableKey($column)) {
                $this->{$column} = $value;
            }
	}
	$this->save();
    }

    public function getPrimaryValues()
    {
        if (is_array($this->_primary_value)) {
            return $this->_primary_value;
        }

        return array($this->_primary_value);
    }

    public function getTableClass()
    {
	return $this->_tableClass;
    }

    public function getTable()
    {
	return Pix_Table::getTable($this->_tableClass);
    }

    /**
     * delete 刪除這個 row
     * 
     * @param mixed $follow_relation 當設為 false 時，就不會順便刪除 delete=true 的 relation
     * @access public
     * @return void
     */
    public function delete($follow_relation = true)
    {
	if (!$this->_primary_value) { 
	    throw new Pix_Table_Exception('這個 Row 還不存在在 DB 上面，不能刪除');
        }

        $table = $this->getTable();

	try {
	    $this->preDelete();
	} catch (Pix_Table_Row_Stop $e) {
	    return;
	}

	if ($follow_relation) {
	    foreach ($table->_relations as $name => $relation) {
		if ($relation['delete']) {
                    if ($relation['rel'] == 'belongs_to' || $relation['rel'] == 'has_one') {
                        if ($this->{$name}) {
                            $this->{$name}->delete();
                        }
		    } else {
			foreach ($this->{$name} as $row) {
			    $row->delete();
			}
		    }
		}
	    }
	}

        $this->getRowDb()->deleteOne($this);
	$this->postDelete();
	$this->cacheRow(null);
	$this->_orig_data = array();
	$this->_data = array();
	$this->_primary_value = null;

        return;
    }

    /**
     * findPrimaryValues 尋找這個 Row 的 PrimaryValues，與 getPrimaryValues 不同的是，getPrimaryValues要已經存在資料庫
     * 內才能得到資料
     *
     * @access public
     * @return null 資料不全 array PrimaryValues
     */
    public function findPrimaryValues()
    {
	$table = $this->getTable();
	$ret = array();
	foreach ($table->getPrimaryColumns() as $c) {
	    if (!isset($this->_data[$c])) {
		return null;
	    }
	    $ret[] = $this->_data[$c];
	}
	return $ret;
    }

    public function init() { }
    public function preSave() { }
    public function preInsert() { }
    public function preUpdate($changed_fields = null) { }
    public function preDelete() { }
    public function postSave() { }
    public function postInsert() { }
    public function postUpdate($changed_fields = null) { }
    public function postDelete() { }

    public function save()
    {
	try {
	    $this->preSave($ret);
	} catch (Pix_Table_Row_Stop $e) {
	    return;
	}

	if ($this->_primary_value) { // UPDATE
	    try {
                $changed_fields = array_diff_assoc($this->_orig_data, $this->_data);
		$this->preUpdate($changed_fields);
	    } catch (Pix_Table_Row_Stop $e) {
		return;
            }

	    $array = array_diff_assoc($this->_data, $this->_orig_data);
	    if (!count($array)) {
		return;
            }

            $this->getRowDb()->updateOne($this, $array);
            $changed_fields = array_diff_assoc($this->_orig_data, $this->_data);
	    $this->refreshRowData();
	    $this->cacheRow($this->_data);
	    $this->postUpdate($changed_fields);
	    $this->postSave();
	    return $this->_primary_value;
	} else { // INSERT
	    try {
		$this->preInsert();
	    } catch (Pix_Table_Row_Stop $e) {
		return;
	    }

	    // 先清空 cache ，以免在資料庫下完 INSERT 和之後更新 cache 之間的空檔會有問題。
	    if ($primary_values = $this->findPrimaryValues()) {
		$this->cacheRow(false);
	    }

            $insert_id = $this->getRowDb()->insertOne($this->getTable(), $this->_data);

            if ($this->_primary_value = $insert_id) {
		$primary_columns = $this->getTable()->getPrimaryColumns();
		$this->_data[$primary_columns[0]] = $this->_primary_value;
	    } else {
		$this->_primary_value = array();
		foreach ($this->getTable()->getPrimaryColumns() as $col) {
		    $this->_primary_value[] = $this->_data[$col];
		}
	    }

	    $this->refreshRowData();
	    $this->cacheRow($this->_data);
	    $this->postInsert();
	    $this->postSave();
	    return $this->_primary_value;
	}
    }

    public function __construct($conf, $no_init = false)
    {
	if (!isset($conf['tableClass'])) {
	    throw new Pix_Table_Exception('建立 Row 必需要指定 tableClass');
	}
	$this->_tableClass = $conf['tableClass'];

	if (isset($conf['data'])) {
	    $this->_primary_value = array();
	    foreach ($this->getTable()->getPrimaryColumns() as $column) {
		if (!isset($conf['data'][$column])) {
                    throw new Pix_Table_Exception("{$this->_tableClass} Row 的資料抓的不完整(缺少 column: {$column})");
		}
		$this->_primary_value[] = $conf['data'][$column];
	    }
	    $this->_data = $conf['data'];
	    $this->_orig_data = $conf['data'];
	} elseif (isset($conf['default'])) {
	    $this->_data = $conf['default'];
	    $this->_orig_data = $conf['default'];
	}

	if (!$no_init) {
	    $this->init();
	}
    }

    public function getColumn($name)
    {
	return $this->_data[$name];
    }

    public function setColumn($name, $value)
    {
	while (Pix_Table::$_verify) {
	    $column = $this->getTable()->_columns[$name];
	    if ($value === null)
		break;
	    switch ($column['type']) {
		case 'int':
		case 'smallint':
		case 'tinyint':
		    // 這邊用 ctype_digit 而不用 is_int 是因為 is_int('123') 會 return false
		    //if (!ctype_digit(strval($value)))
		    // 這邊改用 regex ，因為 ctype_digit 會把負數也傳 false.. orz
                    if (!preg_match('#^[-]?[0-9]+$#', $value)) {
                        throw new Pix_Table_Row_InvalidFormatException($name, $column, $this);
                    }

                    if ($column['unsigned'] && $value < 0) {
                        throw new Pix_Table_Row_InvalidFormatException($name, $column, $this);
                    }
                    break;

		case 'enum':
		    if (is_array($column['list']) and !in_array($value, $column['list'])) {
                        throw new Pix_Table_Row_InvalidFormatException($name, $column, $this);
                    }
		    break;
		case 'varchar':
		case 'char':
/*		    if (strlen($value) > $column['size'])
			throw new Pix_Table_Row_InvalidFormatException($name, $column, $this);
		    break; */
	    }
	    break;
	}
	$this->_data[$name] = $value;
    }

    public function getHook($name)
    {
        if (!$get = $this->getTable()->_hooks[$name]['get']) {
            throw new Pix_Table_Exception("沒有指定 {$name} 的 get 變數");
        }

        if (is_scalar($get)) {
            return $this->{$get}();
        }

        if (is_callable($get)) {
            return call_user_func($get, $this);
        }

        throw new Pix_Table_Exception('不明的 hook 型態');
    }

    public function setHook($name, $value)
    {
        if (!$set = $this->getTable()->_hooks[$name]['set']) {
            throw new Pix_Table_Exception("沒有指定 {$name} 的 set 變數");
        }

        if (is_scalar($set)) {
            return $this->{$set}($value);
        }

        if (is_callable($set)) {
            return call_user_func($set, $this, $value);
        }

        throw new Pix_Table_Exception('不明的 hook 型態');
    }

    public function getRelation($name)
    {
	$table = $this->getTable();
	if (isset($this->_relation_data[$name])) {
	    return $this->_relation_data[$name];
        }

        if (!in_array($table->_relations[$name]['rel'], array('has_one', 'belongs_to'))) {
            $foreign_table = $this->getTable()->getRelationForeignTable($name);
            $foreign_keys = $this->getTable()->getRelationForeignKeys($name);
	    $primary_values = $this->getPrimaryValues();
            $where = array_combine($foreign_keys, $primary_values);

            return $foreign_table->search($where, $this);
	}

        // A has_one B, A: $this->_table B: $type_Table
        $foreign_table = $this->getTable()->getRelationForeignTable($name);
        $foreign_keys = $this->getTable()->getRelationForeignKeys($name);

        $cols = array();
        foreach ($foreign_keys as $column) {
            $cols[] = $this->{$column};
        }

        if ($row = $foreign_table->find($cols, $this)) {
            $this->_relation_data[$name] = $row;
        } else {
            $row = null;
        }
        return $row;
    }

    public function setRelation($name, $value)
    {
	$table = $this->getTable();
	// 如果是 has_many 不給 set
	if ($table->_relations[$name]['rel'] == 'has_many') {
	    throw new Pix_Table_Exception("has_many 不能夠 ->{$name} = \$value; 請改用 ->{$name}[] = \$value");
	} elseif ('has_one' == $table->_relations[$name]['rel'] || 'belongs_to' == $table->_relations[$name]['rel']) {
	    $this->_relation_data[$name] = null;
	    $type = $table->_relations[$name]['type'];
	    $type_table = Pix_Table::getTable($type);

            $foreign_keys = $table->getRelationForeignKeys($name);

	    if (is_scalar($value)) {
		$value = array($value);
	    }

	    if ($value instanceof Pix_Table_Row and $value->getTableClass() == $type) {
		$value = $value->getPrimaryValues();
	    } elseif ($value == null) {
		$value = array(null);
	    } elseif (!is_array($value)) {
                throw new Exception(' = 右邊的東西只能是 Row 或是 PK' . $type . get_class($value));
	    }

	    if (count($value) != count($foreign_keys)) {
		throw new Exception('參數不夠');
	    }

	    $column_values = array_combine($foreign_keys, $value);
	    foreach ($column_values as $column => $value) {
		$this->{$column} = $value;
	    }
	    return;
	} else {
	    throw new Exception('relation 的 rel 只能是 has_many, has_one 或是 belongs_to 喔');
	}
    }

    public function __get($name)
    {
	$table = $this->getTable();
	// State1. 檢查是否在 column 裡面
	if (isset($table->_columns[$name])) {
	    return $this->getColumn($name);
	}

	// State2. 檢查是否在 Relations 裡面
	if (isset($table->_relations[$name])) {
	    return $this->getRelation($name);
	}

	// State3. User 自訂資料
	if ($name[0] === '_') {
	    return $this->_user_data[substr($name, 1)];
	}

	// State5. Aliases 資料
	if ($aliases = $table->_aliases[$name]) {
	    $rel = $this->getRelation($aliases['relation']);
	    if ($aliases['where']) {
		$rel = $rel->search($aliases['where']);
	    }
	    if ($aliases['order']) {
		$rel = $rel->order($aliases['order']);
	    }
	    return $rel;
	}

	// 檢查 hook 資料
	if (isset($table->_hooks[$name])) {
	    return $this->getHook($name);
	}
        throw new Pix_Table_NoThisColumnException("{$this->getTableClass()} 沒有 {$name} 這個 column");
    }

    public function __set($name, $value)
    {
	$table = $this->getTable();
	// State1. 檢查是否在 column 裡面
	if (isset($table->_columns[$name])) {
	    $this->setColumn($name, $value);
	    return;
	}

	// State2. 檢查是否在 Relations 裡面
	if (isset($table->_relations[$name])) {
	    $this->setRelation($name, $value);
	    return;
	}

	// State3. User 自訂資料
	if ($name[0] === '_') {
	    $this->_user_data[substr($name, 1)] = $value;
	    return;
	}

	// 檢查 hook 資料
	if (isset($table->_hooks[$name])) {
	    $this->setHook($name, $value);
	    return;
	}
        throw new Pix_Table_NoThisColumnException("{$this->getTableClass()} 沒有 {$name} 這個 column");
    }

    public function __isset($name)
    {
	$table = $this->getTable();
	if (isset($table->_columns[$name]) or isset($table->_aliases[$name]))
	    return true;

	if ('has_many' == $table->_relations[$name]['rel']) {
	    return true;
	}

	if ($table->_relations[$name]) {
            $row = $this->getRelation($name);
	    if ($row)
		return true;
	    else
		return false;
	}

	if ($name[0] == '_') {
	    return isset($this->_user_data[substr($name, 1)]);
	}
	return false;
    }

    public function toArray()
    {
	$array = array();
	foreach ($this->getTable()->_columns as $name => $temp) {
	    $array[$name] = $this->{$name};
	}
	return $array;
    }

    public function __unset($name)
    {
	if ($name[0] == '_') {
	    unset($this->_user_data[substr($name, 1)]);
        } elseif ('has_one' == $this->getTable()->_relations[$name]['rel']) {
            $foreign_keys = $this->getTable()->getRelationForeignKeys($name);
	    if ($foreign_keys == $this->getTable()->getPrimaryColumns()) {
		throw new Exception('Foreign Key 等於 Primary Key 的 Relation 不可以直接 unset($obj->rel)');
	    }
	    $this->{$name} = null;
	    $this->save();
	} else {
	    throw new Exception('column name 不可以 unset');
	}
    }

    /**
     * _addRow 
     * 
     * @deprecated 已經不再使用
     * @param mixed $row 
     * @param array $arr 
     * @static
     * @access protected
     * @return void
     * @codeCoverageIgnoreStart
     */
    protected static function _addRow($row, array $arr = array())
    {
	foreach ($row->getTable()->_relations as $key => $value) {
	    if (!$value['delete']) {
		continue;
	    }

	    if ($value['rel'] == 'has_one' or $value['rel'] == 'belongs_to') {
		if ($crow = $row->{$key}) {
		    $arr[] = $crow;
		}
	    } else {
		foreach ($row->{$key} as $crow) {
		    $arr = self::_addRow($crow, $arr);
		}
	    }
	}
	return $arr;
    }

    public function createRelation($relation, $values = array())
    {
        $table = $this->getTable();
        if (!$table->_relations[$relation])
            throw new Exception($relation . ' 不是 relation name ，不能 create_' . $relation);

        if (!is_array($values)) {
            $values = array();
        }

        if (!in_array($table->_relations[$relation]['rel'], array('has_one', 'belongs_to'))) {
            return $this->{$relation}->insert($values);
        }

        $foreign_table = $table->getRelationForeignTable($relation);
        $primary_keys = $foreign_table->getPrimaryColumns();

        $foreign_values = array();
        foreach ($table->getRelationForeignKeys($relation) as $key) {
            $foreign_values[] = $this->{$key};
        }
        $row = $foreign_table->createRow($this);

        foreach (array_merge(array_combine($primary_keys, $foreign_values), $values) as $key => $value) {
            if (!isset($foreign_table->_columns[$key]) and !isset($foreign_table->_relations[$key])) {
                continue;
            }
            $row->{$key} = $value;
        }

        $row->save();
        return $this->_relation_data[$relation] = $row;
    }

    public function __call($name, $args)
    {
	$table = $this->getTable();
        if (preg_match('#create_(.+)#', $name, $ret)) {
            return $this->createRelation($ret[1], $args[0]);
        } elseif ($plugin = $table->getPlugin($name)) {
	    return $plugin->call($table->getPluginMap($name), $this, $args);
	} else {
	    throw new Pix_Table_Exception(get_class($this) . " 沒有 $name 這個 function 喔");
	}
    }

    /**
     * getUniqueID 取得這個 row 的 UNIQUE ID, 由 model name + primary value 的 string 組合，任兩個 row 一定不重覆
     * 
     * @access public
     * @return string
     */
    public function getUniqueID()
    {
	return $this->getTableClass() . ':' . json_encode($this->getPrimaryValues());
    }

    /**
     * refreshRowData 去資料庫更新這一個 row 的資料
     *
     * @access public
     * @return void
     */
    public function refreshRowData()
    {
        $db = $this->getRowDb();
        $this->_relation_data = array();

        // 若 db 不支援 immediate_consistency ，就不需要去 db 更新資料了
        if (!$db->support('immediate_consistency')) {
            return;
        }

        // XXX: 保險起見這邊強制從 master 抓
        if ($db->support('force_master')) {
            $old_force_master = Pix_Table::$_force_master;
            Pix_Table::$_force_master = true;
        }

        if ($row = $db->fetchOne($this->getTable(), $this->getPrimaryValues())) {
	    $this->_data = $this->_orig_data = $row;
        }

        if ($db->support('force_master')) {
            Pix_Table::$_force_master = $old_force_master;
        }
    }

    public function getRowDb()
    {
        return $this->getTable()->getDb();
    }

    public function cacheRow($data)
    {
        $this->getTable()->cacheRow($this->findPrimaryValues(), $data);
    }

    /**
     * stop 在 preXXX 動作呼叫這個可以中斷後面的動作
     *
     * @access public
     * @return void
     */
    public function stop()
    {
        throw new Pix_Table_Row_Stop();
    }
}
