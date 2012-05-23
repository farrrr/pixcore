<?php

/**
 * Pix_Array_Volume VolumnMode 的加強版，順便修掉當初的錯字 XD
 * 
 * @copyright 2003-2010 PIXNET
 * @author Shang-Rung Wang <srwang@pixnet.tw> 
 * @license PHP Version 3.0 {@link http://www.php.net/license/3_0.txt}
 */
class Pix_Array_Volume
{
    static public function getFuncs()
    {
	return array('volume');
    }

    static public function volume($array, $options = array())
    {
	return new Pix_Array_Volume_ResultSet($array, $options);
    }
}
