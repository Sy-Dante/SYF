<?php
/**
 * Created by PhpStorm.
 * User: ShenYao
 * Date: 2016/6/16
 * Time: 17:52
 */

namespace Core;


use Common\Debug;

class Config {
    protected static $config = null;

    /**
     * 初始化配置及调试信息
     */
    public static function init() {
        if (static::$config !== null) {
            throw new \Exception('Config is already init.');
        }

        //配置文件
        $config_file = APP_PATH . 'config.php';
        $debug_file = APP_PATH . 'debug.php';
        if (!file_exists($config_file)) {
            file_put_contents($config_file, "<?php \nreturn " . var_export([], true) . ';');
        }

        $config = include $config_file;
        if (!is_array($config)) {
            $config = [];
        }
        //debug.php部分，有debug文件就开启debug模式
        if (is_file($debug_file)) {
            Debug::init(true);
            ini_set('display_errors', 'On');

            $debug = include $debug_file;
            if (is_array($debug)) {
                $config = array_merge($config, $debug);
            }
        } else {
            Debug::init(false);
            ini_set('display_errors', 'Off');
        }
        static::$config = $config;
    }

    /**
     * 获取配置
     * @param string $name
     * @param mixed|null $default
     * @return mixed|null
     */
    public static function get($name = '', $default = null) {
        if (static::$config === null) {
            static::init();
        }
        if ($name === '') {
            return static::$config;
        } else {
            return isset(static::$config[$name]) ? static::$config[$name] : $default;
        }
    }

}