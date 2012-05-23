<?php

/**
 * Pix_Table_ResultSet_Plugin_Volumnmode 
 * 
 * @package Pix_Table
 * @version $id$
 * @copyright 2003-2010 PIXNET
 * @author Shang-Rung Wang <srwang@pixnet.tw> 
 * @license PHP Version 3.0 {@link http://www.php.net/license/3_0.txt}
 */
class Pix_Table_ResultSet_Plugin_Volumnmode
{
    static public function getFuncs()
    {
	return array('volumnmode');
    }

    static public function volumnmode($resultset, $chunk = 100)
    {
	return new Pix_Table_ResultSet_Plugin_Volumnmode_VolumnResultSet($resultset, $chunk);
    }
}
