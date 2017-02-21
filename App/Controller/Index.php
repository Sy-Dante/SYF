<?php
/**
 * Created by PhpStorm.
 * User: ShenYao
 * Date: 2016/6/8
 * Time: 14:42
 */

namespace Controller;


use Common\Debug;
use Common\Func;
use Core\Config;
use Core\Controller;

class Index extends Controller {
    public function index() {
        $links = [
            [
                'title' => 'Google',
                'url' => 'http://google.com/',
            ],
            [
                'title' => 'Baidu',
                'url' => 'http://baidu.com/',
            ],
        ];
        
        $this->assign('links', $links);
        //$this->links = $links;  //另一种实现

        $this->display();
    }
}