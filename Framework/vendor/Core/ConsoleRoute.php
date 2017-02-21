<?php
/**
 * Created by PhpStorm.
 * User: ShenYao
 * Date: 2016/7/14
 * Time: 15:43
 */

namespace Core;


class ConsoleRoute {

    public static function init() {
        if (PHP_SAPI === 'cli') {
            $has_argv = ini_get('register_argc_argv');
            if ($has_argv == 1) {
                global $argv;
                $route = [];
                $params = [];
                $route_prefix = '--';   //路由前缀
                foreach ($argv as $k => $v) {
                    $prefix = substr($v, 0, strlen($route_prefix));
                    if ($prefix === $route_prefix) {
                        //路由
                        $route[] = ltrim($v, $route_prefix);
                    } else {
                        //参数
                        if (strpos($v, '=') !== false) {
                            parse_str($v, $tmp);
                            $params = array_merge($params, $tmp);
                        } else {
                            $params[$k] = $v;
                        }
                    }
                }
                $argv = $params;

                //解析路由
                $count = count($route);
                if ($count === 1) {
                    $controller = 'Index';
                    $action = $route[0];
                } else {
                    $controller = isset($route[0]) ? $route[0] : 'Index';
                    $action = isset($route[1]) ? $route[1] : 'index';
                }
            } else {
                throw new \Exception('Not register `$argv`!');
            }
            define('CONTROLLER', $controller);
            define('ACTION', $action);
        }
    }

}