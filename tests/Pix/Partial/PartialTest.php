<?php

class PartialTest extends PHPUnit_Framework_TestCase
{
    public function testPartial()
    {
        $view = new Pix_Partial(__DIR__);
        $this->assertEquals(trim($view->partial('test.phtml', array('value' => 123))), '[123]');

        $view->value = 456;
        $this->assertEquals(trim($view->partial('test.phtml', $view)), '[456]');
    }

    public function testEscape()
    {
        $view = new Pix_Partial(__DIR__);
        $this->assertEquals($view->escape('<'), htmlspecialchars('<'));
    }

    public function testTrimMode()
    {
        $this->assertEquals(Pix_Partial::getTrimMode(), false);
        $view = new Pix_Partial(__DIR__);
        $this->assertEquals($view->partial('test_trim.phtml'), " a \n b \n");

        Pix_Partial::setTrimMode(true);
        $this->assertEquals(Pix_Partial::getTrimMode(), true);
        $this->assertEquals($view->partial('test_trim.phtml'), "a\nb");

        Pix_Partial::setTrimMode(false);
    }

    public function testCache()
    {
        $view = new Pix_Partial(__DIR__, array('cache_prefix' => 'CachePrefix'));

        $cache = $this->getMock('Pix_Cache', array('load', 'save'));

        $cache_id = 'test';

        $cache->expects($this->once())
            ->method('load')
            ->will($this->returnValueMap(array(
                array('Pix_Partial:CachePrefix:'.sha1(file_get_contents(__DIR__ . '/test.phtml')).':'.$cache_id.':'.(Pix_Partial::getTrimMode() ? 1 : 0), false),
            )));

        $cache->expects($this->once())
            ->method('save')
            ->will($this->returnValueMap(array(
                array('Pix_Partial:CachePrefix:'.sha1(file_get_contents(__DIR__ . '/test.phtml')).':'.$cache_id.':'.(Pix_Partial::getTrimMode() ? 1 : 0), "[789]\n", true),
            )));

        $this->assertEquals($view->partial('test.phtml', array('value' => 789), array('cache_id' => $cache_id, 'cache' => $cache)), "[789]\n");

        // Test Load
        $cache = $this->getMock('Pix_Cache', array('load'));
        $cache_id = 'test';

        $cache->expects($this->once())
            ->method('load')
            ->will($this->returnValueMap(array(
                array('Pix_Partial:CachePrefix:'.sha1(file_get_contents(__DIR__ . '/test.phtml')).':'.$cache_id.':'.(Pix_Partial::getTrimMode() ? 1 : 0), "[789]\n"),
            )));

        $this->assertEquals($view->partial('test.phtml', array('value' => 789), array('cache_id' => $cache_id, 'cache' => $cache)), "[789]\n");

    }

    public function testNoCache()
    {
        $view = new Pix_Partial(__DIR__, array('cache_prefix' => 'CachePrefix'));
        Pix_Partial::setNoCache(true);
        $this->assertEquals(Pix_Partial::getNoCache(), true);

        $cache = $this->getMock('Pix_Cache');
        $this->assertEquals($view->partial('test.phtml', array('value' => 789), array('cache_id' => 'any', 'cache' => $cache)), "[789]\n");

        Pix_Partial::setNoCache(false);
    }

    public function testWriteOnlyMode()
    {
        $view = new Pix_Partial(__DIR__, array('cache_prefix' => 'CachePrefix'));

        $cache = $this->getMock('Pix_Cache', array('save'));

        $cache_id = 'test';

        Pix_Partial::setCacheWriteOnlyMode(true);
        $cache->expects($this->once())
            ->method('save')
            ->will($this->returnValueMap(array(
                array('Pix_Partial:CachePrefix:'.sha1(file_get_contents(__DIR__ . '/test.phtml')).':'.$cache_id.':'.(Pix_Partial::getTrimMode() ? 1 : 0), "[789]\n", true),
            )));

        $this->assertEquals($view->partial('test.phtml', array('value' => 789), array('cache_id' => $cache_id, 'cache' => $cache)), "[789]\n");

        Pix_Partial::setCacheWriteOnlyMode(false);
        // Test Load
        $cache = $this->getMock('Pix_Cache', array('load'));
        $cache_id = 'test';

        $cache->expects($this->once())
            ->method('load')
            ->will($this->returnValueMap(array(
                array('Pix_Partial:CachePrefix:'.sha1(file_get_contents(__DIR__ . '/test.phtml')).':'.$cache_id.':'.(Pix_Partial::getTrimMode() ? 1 : 0), "[789]\n"),
            )));

        $this->assertEquals($view->partial('test.phtml', array('value' => 789), array('cache_id' => $cache_id, 'cache' => $cache)), "[789]\n");
    }
}
