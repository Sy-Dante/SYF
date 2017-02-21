<?php
/**
 * Created by PhpStorm.
 * User: ShenYao
 * Date: 2016/7/14
 * Time: 17:59
 */

namespace Console;


use Core\ConsoleController;
use Workerman\Worker;

class Index extends ConsoleController {

    private $msg = '';

    public function index() {
        var_dump($this->argv);
    }

    public function httpServer() {
        $http_worker = new Worker('http://0.0.0.0:2345');
        $http_worker->count = 4;
        $http_worker->onMessage = function ($connection, $data) {
            echo "test\n";
            $connection->send(print_r($data, true));
        };

        Worker::runAll();
    }

    public function webSocket() {
        $ws_worker = new Worker("websocket://0.0.0.0:2346");
        $ws_worker->count = 4;
        $ws_worker->onMessage = function($connection, $data)
        {
            $connection->send('hello ' . $data);
        };

        Worker::runAll();
    }

    public function tcpServer() {
        $tcp_worker = new Worker('tcp://0.0.0.0:2347'); //telnet 127.0.0.1 2347
        $tcp_worker->count = 4;
        $this->msg = '';
        $tcp_worker->onMessage = function ($connection, $data) {
            $data = trim($data, "\n\r");
            if (empty($data) && !empty($this->msg)) {
                $connection->send("get: {$this->msg}");
                $this->msg = '';
            } else {
                $this->msg .= $data;
            }
        };

        Worker::runAll();
    }

    public function textServer() {
        $tcp_worker = new Worker('text://0.0.0.0:2348'); //telnet 127.0.0.1 2348
        $tcp_worker->count = 4;
        $this->msg = '';
        $tcp_worker->onMessage = function ($connection, $data) {
            $connection->send("get: {$data}");
        };

        Worker::runAll();
    }

}