<?php

/**
 * Pix_UrlArg 將網址包成 Object
 * $urlarg = new Pix_UrlArg;
 * $urlarg->key1 = 'value1';
 * $urlarg->key2 = 'value2';
 * echo $urlarg; # key1=value1&key2=value2
 * 
 * @copyright 2003-2011 PIXNET
 * @todo 無法處理 a[]=1&a[]=2 的網址:
 */
class Pix_UrlArg
{
    public $_args = array();

    public function __call($name, $args)
    {
	if (count($args)) {
	    $this->$name = $args[0];
	} else {
	    return $this->$name;
	}
	return $this;
    }

    public function __construct($str = '')
    {
	foreach (explode('&', $str) as $arg) {
	    list($k, $v) = explode('=', $arg, 2);
	    if (isset($v)) {
		$this->$k = urldecode($v);
	    }
	}
    }

    public function __get($k)
    {
	return $this->_args[$k];
    }

    public function __set($k, $v)
    {
	if (is_null($v)) {
	    unset($this->_args[$k]);
	} else {
	    $this->_args[$k] = $v;
	}
    }

    public function __toString()
    {
	ksort($this->_args);
	$str = '';
	foreach ($this->_args as $k => $v) {
	    if (strlen($str))
		$str .= '&';
	    $str .= sprintf('%s=%s', $k, urlencode($v));
	}
	return $str;
    }
}

