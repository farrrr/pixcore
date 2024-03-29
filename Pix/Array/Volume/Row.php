<?php

class Pix_Array_Volume_Row
{
    protected $_row;
    protected $_array;

    public function __construct($row, $array)
    {
	$this->_row = $row;
	$this->_array = $array;
    }

    public function getRow()
    {
	return $this->_row;
    }

    public function getPos()
    {
	return $this->_array->getPos($this);
    }

    public function getOrder()
    {
	return $this->_array->getOrder($this);
    }

    public function ok()
    {
	return $this->_array->rowOk($this);
    }
}
