<?php
/**
 * Created by PhpStorm.
 * User: ShenYao
 * Date: 2016/6/8
 * Time: 9:16
 */

namespace Core;


use Common\Func;
use Common\Log;
use Exception\RouteException;

class Core {

    public static $error = [
        E_ERROR => 'E_ERROR',
        E_WARNING => 'E_WARNING',
        E_PARSE => 'E_PARSE',
        E_NOTICE => 'E_NOTICE',
        E_CORE_ERROR => 'E_CORE_ERROR',
        E_CORE_WARNING => 'E_CORE_WARNING',
        E_COMPILE_ERROR => 'E_COMPILE_ERROR',
        E_COMPILE_WARNING => 'E_COMPILE_WARNING',
        E_USER_ERROR => 'E_USER_ERROR',
        E_USER_WARNING => 'E_USER_WARNING',
        E_USER_NOTICE => 'E_USER_NOTICE',
        E_STRICT => 'E_STRICT',
        E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
        E_DEPRECATED => 'E_DEPRECATED',
        E_USER_DEPRECATED => 'E_USER_DEPRECATED',
        E_ALL => 'E_ALL',
    ];

    public function __construct() {
        error_reporting(E_ALL);
        date_default_timezone_set('PRC');

        spl_autoload_register('Core\Core::autoload');
    }

    public static function autoload($class) {
        $path = null;
        $class_file = str_replace('\\', '/', $class) . EXT;
        if (file_exists(LIB_PATH . $class_file)) {
            $path = LIB_PATH;
        } elseif (file_exists(APP_PATH . $class_file)) {
            $path = APP_PATH;
        }
        if ($path !== null) {
            $file = $path . $class_file;
            include $file;
        }
    }

    public static function appExceptionHandler(\Exception $e) {
        $cls = get_class($e);
        Log::write("'{$cls}' in {$e->getFile()} line {$e->getLine()} : '{$e->getMessage()}'",
            'Exception');
        if (!IS_DEBUG) {
            Func::error("System Exception!");
        } else {
            //debug模式直接显示异常
            echo <<<HTML
<pre style="font-size:18px;">
Uncaught exception '{$cls}':
    <font color='red'>'{$e->getMessage()}'</font>
    in {$e->getFile()} line {$e->getLine()}

Trace:
<font color='blue'>{$e->getTraceAsString()}</font>

$e
</pre>
HTML;
        }
    }

    public static function appErrorHandler($type, $message, $file, $line, $context) {
        $e_type = static::$error[$type];
        if (IS_DEBUG || !in_array($type, [E_NOTICE, E_WARNING])) {
            Log::write("[{$e_type}] {$file} line {$line} : {$message}", 'Error');
        }
        if (IS_DEBUG) {
            return false;
        } else {
            return true;
        }
    }

    public static function fatalErrorHandler() {
        $e = error_get_last();
        if (in_array($e['type'], [
            E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR,
        ])) {
            $e_type = static::$error[$e['type']];
            Log::write("[{$e_type}] {$e['file']} line {$e['line']} : {$e['message']}",
                'FatalError');
            if (!IS_DEBUG) {
                Func::error("System FatalError{$e['type']}!");
            }
        }
    }

    protected static function createDir() {
        //应用目录
        Func::makeDir(APP_PATH);

        //控制器目录
        Func::makeDir(APP_PATH . CONTROLLER_DIR);

        //模型目录
        Func::makeDir(APP_PATH . MODEL_DIR);

        //视图模板目录
        $view_path = APP_PATH . VIEW_DIR . '/';
        Func::makeDir($view_path);
        define('VIEW_PATH', $view_path);

        //运行相关信息存放目录
        $runtime_path = APP_PATH . RUNTIME_DIR . '/';
        Func::makeDir($runtime_path);
        define('RUNTIME_PATH', $runtime_path);

        //其他文件目录
        $common_path = APP_PATH . COMMON_DIR . '/';
        Func::makeDir($common_path);
        define('COMMON_PATH', $common_path);
        
        //加载用户公共函数库
        $common_file = COMMON_PATH . 'common.php';
        if (file_exists($common_file)) {
            include $common_file;
        }
    }

    public function run() {

        //加载系统公共函数库
        $common_file = __DIR__ . '/../Common/common.php';
        if (file_exists($common_file)) {
            include $common_file;
        }

        //初始化目录
        static::createDir();

        //加载配置及debug
        Config::init();

        //初始化日志系统
        Log::init(RUNTIME_PATH . 'Log/');

        //错误及异常捕获
        set_exception_handler('Core\Core::appExceptionHandler');
        set_error_handler('Core\Core::appErrorHandler');
        register_shutdown_function('Core\Core::fatalErrorHandler');

        //执行路由
        if (PHP_SAPI === 'cli') {
            //初始化终端路由
            ConsoleRoute::init();

            //控制台目录
            Func::makeDir(APP_PATH . CONSOLE_DIR);

            $class = CONSOLE_DIR . '\\' . CONTROLLER;
            $action = ACTION;
        } else {
            //初始化web路由
            Route::init();

            $class = CONTROLLER_DIR . '\\' . CONTROLLER;
            $action = ACTION;
        }

        //执行相应控制器方法
        try {
            if (class_exists($class)) {
                $model = new $class();
                if (method_exists($model, $action)) {
                    $model->$action();
                } else {
                    throw new RouteException('Action [ ' . CONTROLLER . '->' . ACTION . '() ] is not exists!');
                }
            } else {
                throw new RouteException('Controller [ ' . CONTROLLER . ' ] is not exists!');
            }
        } catch (RouteException $e) {
            //Log::write($e->getMessage() . " | URL: {$_SERVER['REQUEST_URI']}", 'Access');
            $msg = 'Unauthorized access!';
            if (IS_DEBUG) {
                $msg = $e->getMessage();
            }
            Func::error($msg);
        }

        die();
    }

}