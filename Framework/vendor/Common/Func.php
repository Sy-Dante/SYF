<?php
/**
 * Created by PhpStorm.
 * User: ShenYao
 * Date: 2016/6/14
 * Time: 8:57
 */

namespace Common;


use Core\Model;
use Core\Route;

class Func {

    /**
     * @param string $url
     * @param string|array $params
     * @return string
     */
    public static function url($url, $params = '') {
        //参数处理
        if (!empty($params)) {
            if (is_array($params)) {
                $params = http_build_query($params);
            } elseif (!is_string($params)) {
                $params = '';
            }
        }
        //链接处理
        $res = preg_match('/^http[s]?\:\/\//', $url);
        if (empty($res) && substr($url, 0, 1) !== '/') {
            $query_arr = explode('/', $url);
            if (count($query_arr) === 1) {
                $url = CONTROLLER . '/' . $url;//当前控制器/方法
            }
            $query = Route::$query . '=' . $url;    //s=控制器/方法
            $url = $_SERVER['PHP_SELF'] . '?' . $query;
        }
        //合并参数
        if (!empty($params)) {
            if (strpos('?', $url) === -1) {
                $url .= '?';
            } else {
                $url .= '&';
            }
        }

        return $url . $params;
    }

    public static function success($msg = '', $data = '') {
        static::ajaxReturn(1, $msg, $data);
    }

    public static function error($msg = '', $data = '') {
        static::ajaxReturn(-1, $msg, $data);
    }

    /**
     * 返回json数据，如果开启了Debug，则同时显示debug信息
     * @param int $status 状态码
     * @param string $msg 状态描述
     * @param mixed $data 其他数据信息
     */
    public static function ajaxReturn($status, $msg = '', $data = '') {
        $ret = [
            'status' => $status,
            'msg' => $msg,
            'data' => $data,
        ];
        if (IS_DEBUG) {
            Debug::set('sql', Model::getSqlHistory());
            Debug::set('load_file', get_included_files());
            $ret['debug'] = Debug::get();
        } else {
            //正式环境忽略其他输出，只输出json
            ob_get_clean();
        }
        die(json_encode($ret));
    }

    public static function page($data, $page = 1, $length = 6) {
        $start = ($page - 1) * $length;
        $ret = [];
        if (!empty($data) && is_array($data)) {
            $ret = array_slice($data, $start, $length, true);
        }
        return $ret;
    }

    public static function makeDir($dir, $mode = 0755) {
        if (!is_dir($dir)) {
            $res = mkdir($dir, $mode);
            if ($res == false) {
                throw new \Exception("Create Dir[{$dir}] Failed.");
            }
        }
    }

    /**
     * 数据过滤，过滤一个二维数组内的字段
     * @param array $data 一个二维数组
     * @param array $fields 要去除的字段
     * @return mixed
     */
    public static function dataFilter($data, $fields = []) {
        if (!empty($data) && is_array($data) && !empty($fields)) {
            foreach ($data as $k => $v) {
                foreach ($fields as $field) {
                    if (isset($v[$field])) {
                        unset($data[$k][$field]);
                    }
                }
            }
        }
        return $data;
    }

}