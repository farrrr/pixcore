<?php

/**
 * Pix_Exception 
 * 
 * @uses Exception
 * @version $id$
 * @copyright 2003-2010 PIXNET
 * @author Shang-Rung Wang <srwang@pixnet.tw> 
 * @license PHP Version 3.0 {@link http://www.php.net/license/3_0.txt}
 */
class Pix_Exception extends Exception
{
    public $errorcode;
    public $options;

    public function __construct($message = null, $errorcode = null, $options = null)
    {
	parent::__construct($message);
	$this->errorcode = $errorcode;
	$this->options = $options;
    }
}

