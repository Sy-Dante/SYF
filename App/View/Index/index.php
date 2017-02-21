<?php
/**
 * Created by PhpStorm.
 * User: ShenYao
 * Date: 2016/6/13
 * Time: 17:34
 */

foreach ($links as $link) {
    echo "<a href='{$link['url']}' target='_blank'>{$link['title']}</a><br>";
}