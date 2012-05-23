<?php

/**
 * Pix_Table_Db 
 * 
 * @package Pix_Table
 * @version $id$
 * @copyright 2003-2010 PIXNET
 * @author Shang-Rung Wang <srwang@pixnet.tw> 
 * @license PHP Version 3.0 {@link http://www.php.net/license/3_0.txt}
 */
class Pix_Table_Db
{
    static public function factory($obj)
    {
	if (is_object($obj) and 'mysqli' == get_class($obj)) {
	    return new Pix_Table_Db_Adapter_Mysqli($obj);
	} elseif (is_array($obj) and isset($obj['cassandra'])) {
	    return new Pix_Table_Db_Adapter_Cassandra($obj['cassandra']);
	} elseif (is_object($obj) and is_a($obj, 'Pix_Table_Db_Adapter')) {
	    return $obj;
	}

	throw new Exception('不知道是哪類的 db' . get_class($obj));
    }
}
