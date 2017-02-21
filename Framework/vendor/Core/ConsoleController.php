<?php
/**
 * Created by PhpStorm.
 * User: ShenYao
 * Date: 2016/7/14
 * Time: 17:51
 */

namespace Core;


class ConsoleController {
    protected $argv = null;
    protected $argc = 0;

    public function __construct() {
        global $argv;
        $this->argv = $argv;
        $this->argc = count($argv);

        $this->init();
    }

    protected function init() {}
}