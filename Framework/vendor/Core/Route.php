<?php
/**
 * Created by PhpStorm.
 * User: ShenYao
 * Date: 2016/6/8
 * Time: 9:55
 */

namespace Core;


class Route {
    public static $controller = 'c';
    public static $action = 'a';
    public static $query = 's';
    public static $postAction = 'act';

    public static function init() {
        static::setFlag();

        $url = $_SERVER['REQUEST_URI'];
        //强制忽略掉.php前面的部分
        $ext = '.php';
        $position = strpos($url, $ext);
        if ($position) {
            $url = substr($url, $position + strlen($ext));
            $url = (substr($url, 0, 1) === '/' ? '' : '/') . $url;
        }
        $params = static::parseUrl($url);
        $_GET = array_merge($params, $_GET);

        //当前访问的控制器
        define('CONTROLLER', $_GET[static::$controller]);
        //当前要执行的操作
        define('ACTION', $_GET[static::$action]);

        //删除与路由相关的请求参数
        unset($_GET[static::$controller]);
        unset($_REQUEST[static::$controller]);
        unset($_GET[static::$action]);
        unset($_REQUEST[static::$action]);
        unset($_GET[static::$query]);
        unset($_REQUEST[static::$query]);
    }

    protected static function parseUrl($url) {
        $params = [];
        if (false !== strpos($url, '?')) { //[控制器/操作?]参数1=值1&参数2=值2...
            $info = parse_url($url);
            if (isset($info['path'])) {
                $path = explode('/', trim($info['path'], '/'));
            }
            if (isset($info['query'])) {
                parse_str($info['query'], $params);
            }
        } elseif (strpos($url, '/') !== false) { //[控制器/操作]
            $path = explode('/', trim($url, '/'));
        } else { //参数1=值1&参数2=值2...
            parse_str($url, $params);
        }

        //解析$path
        if (isset($path)) {
            $params[static::$controller] = array_shift($path);
            if (!empty($path)) {
                $params[static::$action] = array_shift($path);
            } else {
                $params[static::$action] = $params[static::$controller];
                unset($params[static::$controller]);
            }
            while (!empty($path) && count($path) > 1) {
                $key = array_shift($path);
                $value = array_shift($path);
                $params[$key] = $value;
            }
        }

        //$_REQUEST['act'] = 操作
        if (!empty($_REQUEST[static::$postAction])) {
            $params[static::$action] = $_REQUEST[static::$postAction];
        }

        //[s=控制器/操作]
        if (!empty($params[static::$query])) {
            $params[static::$query] = trim($params[static::$query], '/');
            $s = explode('/', $params[static::$query]);
            if (count($s) >= 2) {
                list($params[static::$controller], $params[static::$action]) = $s;
            }
        }
        //[c=控制器]
        if (empty($params[static::$controller])) {
            $params[static::$controller] = 'Index';
        }
        $params[static::$controller] = ucwords($params[static::$controller]);

        //[a=操作]
        if (empty($params[static::$action])) {
            $params[static::$action] = 'index';
        }

        return $params;
    }

    protected static function setFlag() {
        $config = Config::get('URL');
        if (!empty($config)) {
            if (!empty($config['controller'])) static::$controller = $config['controller'];
            if (!empty($config['action']))  static::$action = $config['action'];
            if (!empty($config['query'])) static::$query = $config['query'];
        }
    }
}