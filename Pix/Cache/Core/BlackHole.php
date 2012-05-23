<?php

/**
 * Pix_Cache_Core_BlackHole
 * 
 * @uses Pix_Cache_Core
 * @package Pix_Cache
 * @version $id$
 * @copyright 2003-2009 PIXNET
 * @license 
 */
class Pix_Cache_Core_BlackHole extends Pix_Cache_Core
{
    public function __construct($config)
    {
    }

    public function add($key, $value, $options = array())
    {
    }

    public function set($key, $value, $options = array())
    {
    }

    public function delete($key)
    {
    }

    public function replace($key, $value, $options = array())
    {
    }

    public function inc($key, $inc = 1)
    {
    }

    public function dec($key, $inc = 1)
    {
    }

    public function append($key, $data, $options = array())
    {
    }

    public function prepend($key, $data, $options = array())
    {
    }

    public function get($key)
    {
	return false;
    }

    public function gets(array $keys)
    {
	return false;
    }
}
