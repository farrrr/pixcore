<?php

/**
 * Pix_Table_Plugin 
 * 
 * @package Pix_Table
 * @version $id$
 * @copyright 2003-2010 PIXNET
 * @author Shang-Rung Wang <srwang@pixnet.tw> 
 * @license PHP Version 3.0 {@link http://www.php.net/license/3_0.txt}
 */
class Pix_Table_Plugin
{
    protected $_options = array();

    public function __construct($options = array())
    {
	$this->_options = is_array($options) ? $options : array();
    }

    protected function getOption($key)
    {
	return $this->_options[$key];
    }

    public function init($row)
    {
    }

    public function call($method, $row, $args)
    {
	array_unshift($args, $row);

	$this->init($row);

	return call_user_func_array(array($this, $method), $args);
    }
}
