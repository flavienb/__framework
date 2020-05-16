<?php
/**
 * @author flavienb.com
 */

define('ROOT_PATH',str_replace('public','',__DIR__));
define('APPLICATION_PATH', ROOT_PATH.'application/');
define('FRAMEWORK_PATH', APPLICATION_PATH.'__framework/');
define('PUBLIC_PATH', ROOT_PATH . 'public/');

require(APPLICATION_PATH . 'config/modules.php');

__framework::init();
