<?php

/**
 * Pix_Table_Row_InvalidFormatException 
 * 
 * @uses Exception
 * @package Pix_Table
 * @version $id$
 * @copyright 2003-2010 PIXNET
 * @author Shang-Rung Wang <srwang@pixnet.tw> 
 * @license PHP Version 3.0 {@link http://www.php.net/license/3_0.txt}
 */
class Pix_Table_Row_InvalidFormatException extends Exception
{
    public $column;

    public function __construct($name, $column, $row)
    {
        parent::__construct($row->getTableClass() . '的欄位 ' . $name. ' 格式不正確');

	$this->column = $column;
    }
}
