<?php
/**
 * Pix_Table_Plugin_Fluctuate
 *
 * @uses Pix
 * @uses _Table_Plugin
 * @package Pix_Table
 * @version $id$
 * @copyright 2003-2011 PIXNET
 * @author Manic Chuang <manic@pixnet.tw>
 * @license
 */
class Pix_Table_Plugin_Fluctuate extends Pix_Table_Plugin
{

    protected function _getTable($row)
    {
	if (!$this->table) {
	    $this->table = $row->getTable();
	}
	return $this->table;
    }

    public function increment($row, $column, $delta = 1)
    {
        $op = '+';
        $db = $row->getRowDb();
        $row->update($db->column_quote($column) . ' = ' . $db->column_quote($column) . $op . intval($delta));

    }

    public function decrement($row, $column, $delta = 1)
    {
        $op = '-';
        $db = $row->getRowDb();
        $row->update($db->column_quote($column) . ' = ' . $db->column_quote($column) . $op . intval($delta));
    }
}
