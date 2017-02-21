<?php
/**
 * Created by PhpStorm.
 * User: ShenYao
 * Date: 2016/6/17
 * Time: 11:09
 */

namespace Common;


/**
 * Class Debug
 * 调试模式时，设置调试信息并显示
 * @package Common
 */
class Debug {

    protected static $info = [];

    public static function init($status) {
        if (defined('IS_DEBUG')) {
            throw new \Exception('Debug model already init!');
        }
        define('IS_DEBUG', $status);
    }

    public static function set($title, $data=null) {
        if ($data === null) {
            static::$info[] = $title;
        } elseif (isset(static::$info[$title])) {
            static::$info[$title] = [
                'is_replace' => true,
                'data' => $data,
            ];
        } else {
            static::$info[$title] = $data;
        }
    }

    public static function get() {
        $ret = '';
        if (IS_DEBUG) {
            $ret = static::$info;
        }
        return $ret;
    }
}