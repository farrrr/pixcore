<?php

/**
 * Pix_Array_Volume_ResultSet
 * 
 * @uses Iterator
 * @copyright 2003-2010 PIXNET
 * @author Shang-Rung Wang <srwang@pixnet.tw> 
 * @license PHP Version 3.0 {@link http://www.php.net/license/3_0.txt}
 */
class Pix_Array_Volume_ResultSet implements Iterator, countable
{
    protected $_array = null;
    protected $_chunk = 100;
    protected $_last_row = null;
    protected $_limit = null;

    /**
     * __construct 設定一次要取多少筆
     * 
     * @param Pix_Array $array
     * @param int $chunk 
     * @access public
     */
    public function __construct($array, $options = array())
    {
        $this->_origin_array = $array;
	$this->_chunk = isset($options['chunk']) ? intval($options['chunk']) : 100;
	$this->_volume_id = isset($options['id']) ? $options['id'] : $array->getVolumeID();
    }

    public function rewind()
    {
	$this->_limit = $this->_origin_limit;
	$this->_offset = $this->_origin_offset;
        $this->_pos = $this->_origin_offset;
        $this->_last_row = null;
	$this->_array = Pix_Array::factory(
	    $this->_origin_array
	    ->after($this->_origin_array->getVolumePos($this->_last_row))
	    ->limit($this->_chunk)
	    ->rewind()
	);
	return $this;
    }

    public function first()
    {
        return $this->rewind()->current();
    }

    public function current()
    {
	$this->_last_row = $this->_array->current();
	return new Pix_Array_Volume_Row($this->_last_row, $this);
    }

    public function key()
    {
        return $this->_array->key();
    }

    public function next()
    {
        $this->_array->next();
	if (0 !== $this->_limit and !$this->valid()) {
            $this->_array = Pix_Array::factory($this->_origin_array->after($this->_origin_array->getVolumePos($this->_last_row))->limit($this->_chunk)->rewind());
	}
    }

    public function valid()
    {
	if (0 === $this->_limit) {
	    return false; 
	}
        return $this->_array->valid();
    }

    public function offset($offset)
    {
	$this->_origin_offset = $offset;
	return $this;
    }

    public function after($row)
    {
        $rs = clone $this;
        $rs->_last_row = $row;
        $rs->_after = $row;
        return $rs;
    }

    public function limit($limit)
    {
	$this->_origin_limit = $limit;
	return $this;
    }

    public function rowOk($row)
    {
	if (intval($this->_offset) > 0) {
	    $this->_offset --;
	    return false;
	}
	if (is_null($this->_limit)) {
	    $this->_pos ++;
	    return true;
	}
	if (($this->_limit --) >= 0) {
	    $this->_pos ++;
	    return true;
	}
	return false;
    }

    /**
     * getPos 取得一個 string ，之後可以當作 after 來用。 
     * 
     * @access public
     * @return void
     */
    public function getPos($row)
    {
	return $this->_array->getVolumePos($row->getRow());
    }

    public function getOrder($row)
    {
	return $this->_pos;
    }

    public function count()
    {
	return $this->_origin_array->count();
    }
}
