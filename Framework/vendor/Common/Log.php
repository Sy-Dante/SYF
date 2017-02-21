<?php
/**
 * Created by PhpStorm.
 * User: ShenYao
 * Date: 2016/6/17
 * Time: 15:39
 */

namespace Common;


class Log {
    protected static $format = "[%s] %s : %s\r\n";
    public static $log_dir = null;

    public static function init($dir) {
        if (static::$log_dir === null) {
            static::$log_dir = $dir;
        } else {
            throw new \Exception('Log model already init.');
        }
    }

    public static function write($message, $tag) {
        Func::makeDir(static::$log_dir);
        if (!is_writable(static::$log_dir)) {
            throw new \Exception('Log dir not writable!');
        }
        $log_file = static::$log_dir . date('Ymd') . '.log';
        file_put_contents($log_file, sprintf(static::$format, $tag, date('Y-m-d H:i:s'), $message), FILE_APPEND);
    }

}