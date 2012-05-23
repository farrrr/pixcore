<?php

/**
 * Pix_Table_ResultSet_Plugin_Volumnmode_VolumnResultSetArray 
 * 
 * @uses Iterator
 * @package Pix_Table
 * @version $id$
 * @copyright 2003-2010 PIXNET
 * @author Shang-Rung Wang <srwang@pixnet.tw> 
 * @license PHP Version 3.0 {@link http://www.php.net/license/3_0.txt}
 */
class Pix_Table_ResultSet_Plugin_Volumnmode_VolumnResultSetArray implements Iterator
{
    protected $_result_set = null;
    protected $_columns = null;
    protected $_chunk = 100;

    public function __construct($result_set, $chunk = 100, $columns = null)
    {
        $this->_result_set = $result_set;
	$this->_chunk = $chunk;
	$this->_columns = $columns;
    }

    public function __call($name, $args)
    {
        $this->_result_set = call_user_func_array(array($this->_result_set, $name), $args);
        if (!is_object($this->_result_set) or !is_a($this->_result_set, 'Pix_Table_ResultSet')) {
            throw new Exception('無法使用 ->{' . $name . '}');
        }
        return $this;
    }

    public function rewind()
    {
        $this->_result_set = $this->_result_set->limit($this->_chunk)->rewind();
    }

    public function current()
    {
	$result_set = clone $this->_result_set;
	return $result_set->toArray($this->_columns);
    }

    public function key()
    {
        return $this->_result_set->key();
    }

    public function next()
    {
	$last_row = null;
	while ($this->_result_set->valid()) {
	    $last_row = $this->_result_set->current();
	    $this->_result_set->next();
	}
	$this->_result_set = $this->_result_set->after($last_row)->limit($this->_chunk)->rewind();
    }

    public function valid()
    {
        return $this->_result_set->valid();
    }
}


