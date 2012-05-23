<?php

/**
 * Pix_Array_Cache
 * 將 sortable key/value 的 array ，用 linked list 塞進 cache 中
 * 
 * @uses Pix
 * @uses _Array
 * @package Pix_Array
 * @version $id$
 * @copyright 2003-2009 PIXNET
 * @author Shang-Rung Wang <srwang@pixnet.tw> 
 * @license 
 */
class Pix_Array_Cache extends Pix_Array
{
    protected $_limit = null;
    protected $_cur_data = array();
    protected $_after_key = null;

    protected $_cache;
    protected $_prefix;
    protected $_cluster_max = 2;

    /**
     * __construct 
     * 
     * @param Pix_Cache $cache 
     * @param string $prefix 
     * @access public
     * @return void
     */
    public function __construct(Pix_Cache $cache, $prefix = null, $cluster_max = 100)
    {
	$this->_cache = $cache;
	$this->_prefix = is_null($prefix) ? 'Pix_Array_Cache:' . crc32(uniqid()) : $prefix;
	$this->_cluster_max = $cluster_max;
    }

    /**
     * _getKey 取得 Cache key
     * 
     * @param mixed $key 
     * @access protected
     * @return void
     */
    protected function _getKey($key)
    {
	return $this->_prefix . ':' . $key;
    }

    protected $_last_update = null;
    protected $_index_data = array();

    public function _getIndex()
    {
	$last_update = intval($this->_cache->load($this->_getKey('update')));
	if (!is_null($this->_last_update) and $this->_last_update == $last_update) {
	    return $this->_index_data;
	}
	$this->_last_update = $last_update;

	if (!$index = $this->_cache->load($this->_getKey('index'))) {
	    return array();
	}
	if (!$index_data = @json_decode($index, true)) {
	    return array();
	}

	return $this->_index_data = $index_data;
    }

    protected function _getData($max_key)
    {
	if (!$data = $this->_cache->load($this->_getKey('data:' . $max_key))) {
	    return array();
	}
	if (!$data = @json_decode($data, true)) {
	    return array();
	}

	return $data;
    }

    protected function _saveData($max_key, $data)
    {
	ksort($data);
	$this->_cache->save($this->_getKey('data:' . $max_key), json_encode($data));
    }

    protected function _saveIndex($index_data)
    {
	ksort($index_data);
	$this->_last_update = time();
	$this->_index_data = $index_data;

	$this->_cache->set($this->_getKey('index'), json_encode($index_data));
	$this->_cache->set($this->_getKey('update'), $this->_last_update);
    }



    public function getRand($count = null)
    {
	throw new Exception('not implement');
    }

    public function offset($offset = 0)
    {
	throw new Exception('not implement');
    }

    public function order($order = null)
    {
	// only order by key
	return;
    }

    public function limit($limit = null)
    {
	$this->_limit = $limit;
	return $this;
    }

    public function sum($column = null)
    {
	throw new Exception('not implement');
    }

    public function max($column = null)
    {
	throw new Exception('not implement');
    }

    public function min($column = null)
    {
	throw new Exception('not implement');
    }

    public function first()
    {
	return $this->rewind()->current();
    }

    public function toArray($column = null)
    {
	if (!$column) {
	    return $this->rewind()->_cur_data;
	}
	$arr = array();
	foreach ($this->rewind()->_cur_data as $data) {
	    if ($data[$column]) {
		$arr[] = $data[$column];
	    }
	}
	return $arr;
    }

    public function getPosition($obj)
    {
	throw new Pix_Array_Exception('TODO');
    }

    public function count()
    {
	// TODO
    }

    public function seek($pos)
    {
	return $this->offsetExists($pos);
    }

    public function current()
    {
	return current($this->_cur_data);
    }

    public function next()
    {
	next($this->_cur_data);
	return $this;
    }

    public function key()
    {
	return key($this->_cur_data);
    }

    public function valid()
    {
	return isset($this->_cur_data[key($this->_cur_data)]);
    }

    public function rewind()
    {
	$index_data = $this->_getIndex();

	$this->_cur_data = array();
	foreach ($index_data as $max_key => $count) {
	    // skip before $_after
	    if (!is_null($this->_after) and $max_key <= $this->_after) continue;

	    $data = $this->_getData($max_key);

	    foreach ($data as $key => $value){
		if (!is_null($this->_after) and $key <= $this->_after) continue;
		$this->_cur_data[$key] = $value;
		if (!is_null($this->_limit) and count($cur_data[$key]) >= $this->_limit) break 2;
	    }
	}

	krsort($this->_cur_data);

	return $this;
    }

    public function offsetExists($pos)
    {
	$index_data = $this->_getIndex();
	foreach ($index_data as $max_key => $count) {
	    if ($max_key > $key) break;
	}

	$data = $this->_getData($max_key);
	return array_key_exists($key, $data);
    }

    public funCtion offsetGet($key)
    {
	$index_data = $this->_getIndex();
	foreach ($index_data as $max_key => $count) {
	    if ($max_key > $key) break;
	}

	$data = $this->_getData($max_key);
	return $data[$key];
    }

    public function offsetSet($key, $value)
    {
	$index_data = $this->_getIndex();
	$max_key = null;
	$count = 0;
	foreach ($index_data as $max_key => $count) {
	    if ($max_key > $key) break;
	}

	if (is_null($max_key)) { // 表示原先完全沒資料
	    $data = array();
	} else { // 最後一頁還是一樣大於
	    $data = $this->_getData($max_key);
	    unset($index_data[$max_key]);
	}

	$data[$key] = $value;
	if (count($data) >= $this->_cluster_max) {
	    $datas = array_chunk($data, ceil($this->_cluster_max / 2), true);
	    foreach ($datas as $d) {
		$index_data[max(array_keys($d))] = count($d);
		$this->_saveData(max(array_keys($d)), $d);
	    }
	} else {
	    $index_data[max(array_keys($data))] = count($data);
	    $this->_saveData(max(array_keys($data)), $data);
	}
	$this->_saveIndex($index_data);
    }

    public function offsetUnset($pos)
    {
	$index_data = $this->_getIndex();
	foreach ($index_data as $max_key => $count) {
	    if ($max_key > $key) break;
	}

	$data = $this->_getData($max_key);
	if (isset($data[$key])) {
	    $index_data[$max_key] --;
	    $this->_saveIndex($index_data);
	}
	unset($data[$key]);
	$this->_saveData($max_key, $data);
    }

    public function after($after_key)
    {
	$this->_after = $after_key;
	return $this;
    }

    public function truncate()
    {
	$this->_saveIndex(array());
    }

    public function merge($merge)
    {
	// TODO
    }
}
