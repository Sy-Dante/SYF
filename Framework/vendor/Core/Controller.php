<?php
/**
 * Created by PhpStorm.
 * User: ShenYao
 * Date: 2016/6/13
 * Time: 16:44
 */

namespace Core;


use Common\Debug;
use Common\Func;

class Controller {
    private $tpData = []; //模板赋值数据
    protected static $separator = '/'; //模板存储方式，默认为[View/Controller/action.php]
    protected $noCheck = [];    //不进行验证的操作
    protected $timeout = 0.5; //签名过期时间，单位为：分钟
    protected $debugFile = '';

    public function __construct() {
        //修改模板存储方式，如template_separator = '_'，则存储地址为[View/Controller_action.php]
        $separator = Config::get('template_separator');
        if ($separator !== null) {
            static::$separator = $separator;
        }

        //Debug模式下不验证签名
        if (!IS_DEBUG && !in_array(ACTION, $this->noCheck)) {
            $res = $this->checkSign();
            $sign_switch = Config::get('check_sign', true); //默认开启签名验证
            if ($res !== true && $sign_switch) {
                Func::error($res);
            }
        } else {
            //Debug模式下显示是否通过签名
            //Debug::set('AccessKey', Config::get('AccessKey'));
            Debug::set('time', $this->request('time', 0));
            Debug::set('sign', $this->request('sign', 0));
            Debug::set('checkSign', $this->checkSign());
        }
        $this->init();
    }

    /**
     * 初始化
     */
    protected function init() {}

    /**
     * 签名验证
     * @return string|bool 为true表示通过，否则表示签名不通过的原因
     */
    protected function checkSign() {
        $now = time();
        $time = $this->request('time', 0);
        $time = is_numeric($time) ? $time : 0;
        $interval = $now - $time;
        if ($interval > $this->timeout * 60 || $interval < 0) {
            return 'Timeout!';
        } else {
            $sign = $this->request('sign', 0);
            $sign_str = $this->createSignStr();
            return $sign_str === $sign ? true : 'Sign error!';
        }
    }

    /**
     * 构建签名
     * @return string
     */
    protected function createSignStr() {
        $key = Config::get('AccessKey');
        $arr = array_merge($_GET, $_POST);
        unset($arr['sign']);
        ksort($arr);
        $sign_arr = [];
        foreach ($arr as $k => $v) {
            if (!is_string($v)) {
                $v = serialize($v);
            }
            $sign_arr[] = "{$k}={$v}";
        }
        $sign_str = implode('&', $sign_arr);
        Debug::set('sign_str', $sign_str);

        $sign_str = md5($sign_str . $key);
        Debug::set('server_sign', $sign_str);

        return $sign_str;
    }

    /**
     * 获取$_GET参数
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    final protected function get($name, $default = null) {
        return $this->getInput($_GET, $name, $default);
    }

    /**
     * 获取$_POST参数
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    final protected function post($name, $default = null) {
        return $this->getInput($_POST, $name, $default);
    }

    /**
     * 获取$_REQUEST参数
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    final protected function request($name, $default = null) {
        return $this->getInput($_REQUEST, $name, $default);
    }

    /**
     * 获取$_SESSION参数
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    final protected function session($name, $default = null) {
        return $this->getInput($_SESSION, $name, $default);
    }

    /**
     * 获取$_COOKIE参数
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    final protected function cookie($name, $default = null) {
        return $this->getInput($_COOKIE, $name, $default);
    }

    /**
     * 获取数组内参数
     * @param array $request
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    final protected function getInput($request, $name, $default) {
        return isset($request[$name]) ? $request[$name] : $default;
    }

    /**
     * 模板内变量赋值
     * @param string $name
     * @param mixed $value
     */
    final protected function assign($name, $value = null) {
        if (is_array($name)) {
            foreach ($name as $k => $v) {
                $this->tpData[$k] = $v;
            }
        } else {
            $this->tpData[$name] = $value;
        }
    }

    /**
     * 渲染模板
     * @param string $filename
     * @param bool $debug
     * @throws \Exception
     */
    protected function display($filename = '', $debug = true) {
        if (empty($filename)) {
            $filename = CONTROLLER . static::$separator . ACTION;
        }
        $tp_file = VIEW_PATH . $filename . '.php';
        $tp_file = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $tp_file);
        if (is_file($tp_file)) {
            $this->includeTemplate($tp_file);
            if ($debug && IS_DEBUG) {
                $this->includeDebug();
            }
            die();
        } else {
            throw new \Exception("Template file: [ {$tp_file} ] not exists!");
        }
    }

    /**
     * 载入模板文件，避免数据污染
     * @param string $tp_file 模板实际路径
     */
    final protected function includeTemplate($tp_file) {
        _include_template($tp_file, $this->tpData);
    }

    /**
     * 调试信息展示
     */
    protected function includeDebug() {
        if (is_file($this->debugFile) && substr($this->debugFile, -4) === '.php'
        ) {
            _include_template($this->debugFile, ['debug' => Debug::get()]);
        } else {
            echo "\n<br>\n--------------\n<br>\nDebug:\n<br>\n<pre>\n";
            var_dump(Debug::get());
            echo "\n</pre>\n";
        }
    }

    /**
     * 设置模版内变量的魔术方法
     * @param string $name
     * @param mixed $value
     */
    final public function __set($name, $value) {
        $this->tpData[$name] = $value;
    }
}

/**
 * 包含模板文件，注入数据
 * @param string $_file 模板文件地址
 * @param array $_data 数据
 */
function _include_template($_file, $_data) {
    if (is_array($_data)) {
        extract($_data);
    } else {
        $data = $_data;
    }
    unset($_data);
    include $_file;
}