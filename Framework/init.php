<?php
/**
 * Created by PhpStorm.
 * User: ShenYao
 * Date: 2016/6/8
 * Time: 9:14
 */

defined('APP_NAME') or define('APP_NAME', 'App');

defined('APP_PATH') or define('APP_PATH', __DIR__ . '/' . APP_NAME .'/');

defined('LIB_PATH') or define('LIB_PATH', __DIR__ . '/vendor/');

define('CONTROLLER_DIR', 'Controller');
define('MODEL_DIR', 'Model');
define('VIEW_DIR', 'View');
define('COMMON_DIR', 'Common');
define('RUNTIME_DIR', 'Runtime');
define('CONSOLE_DIR', 'Console');

//类文件后缀
define('EXT', '.php');

include LIB_PATH . 'Core/Core' . EXT;

(new \Core\Core())->run();