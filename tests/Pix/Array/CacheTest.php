<?php

require_once(dirname(__FILE__) . '/../../init.inc.php');

class Pix_Array_CacheTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if (!class_exists('Memcache')) {
            $this->markTestSkipped('The memcache extension is not available');
        }
    }

    public function test()
    {
	Pix_Cache::addServer('Pix_Cache_Core_Memcache', array('host' => PIXCORE_TEST_MEMCACHEHOST, 'port' => PIXCORE_TEST_MEMCACHEPORT), 'test');
	$cache = new Pix_Cache('test');

	$array = new Pix_Array_Cache($cache, null, 3);

	$array[3] = 4;
	$array[8] = 5;
	$array[9] = 6;
	$array[7] = 7;
	$array[4] = 8;
    }
}
