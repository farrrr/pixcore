<?php

// 請指定 pixcore-php 位置
define(PIXCORE_PATH, dirname(__FILE__) . '/extlibs/pixcore-php');

require(PIXCORE_PATH . '/Pix/Loader.php');

set_include_path(PIXCORE_PATH
    . PATH_SEPARATOR . dirname(__FILE__) . '/models'
);

Pix_Loader::registerAutoload();

Pix_Session::setCore('cookie', array('secret' => '__MODIFY_ME_'));

Pix_Controller::addCommonPlugins();
/*
Pix_Controller::addDispatcher(function($uri){
   return array('user', 'list', $user);
});``
 */
Pix_Controller::dispatch(dirname(__FILE__));
