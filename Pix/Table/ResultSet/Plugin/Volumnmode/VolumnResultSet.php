<?php

/**
 * Pix_Table_ResultSet_Plugin_Volumnmode_VolumnResultSet 
 * 
 * @uses Iterator
 * @package Pix_Table
 * @version $id$
 * @copyright 2003-2010 PIXNET
 * @author Shang-Rung Wang <srwang@pixnet.tw> 
 * @license PHP Version 3.0 {@link http://www.php.net/license/3_0.txt}
 */
class Pix_Table_ResultSet_Plugin_Volumnmode_VolumnResultSet implements Iterator, countable
{
    protected $_result_set = null;
    protected $_chunk = 100;
    protected $_last_row = null;

    public function __construct($result_set, $chunk = 100)
    {
        $this->_result_set = $result_set;
        $this->_chunk = $chunk;
    }

    public function rewind()
    {
        $this->_result_set = $this->_result_set->after($this->_last_row)->limit($this->_chunk)->rewind();
    }

    public function current()
    {
	return $this->_last_row = $this->_result_set->current();
    }

    public function key()
    {
        return $this->_result_set->key();
    }

    public function next()
    {
        $this->_result_set->next();
        if (!$this->valid()) {
            $this->_result_set = $this->_result_set->after($this->_last_row)->limit($this->_chunk)->rewind();
        }
    }

    public function valid()
    {
        return $this->_result_set->valid();
    }

    public function count()
    {
	return count($this->_result_set->after($this->_last_row));
    }

    public function after($row)
    {
	$this->_last_row = $row;
	return $this;
    }

    public function toArray($columns = null)
    {
	return new Pix_Table_ResultSet_Plugin_Volumnmode_VolumnResultSetArray($this->_result_set, $this->_chunk, $columns);
    }
}

