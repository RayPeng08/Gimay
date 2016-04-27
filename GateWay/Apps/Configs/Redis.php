<?php
/**
 * Created by PhpStorm.
 * User: 彭泽龙
 * Date: 2016-4-13
 * Time: 16:22
 * Redis配置文件
 */
$redis['master'] = array(
    'host'    => "127.0.0.1",
    'port'    => 6379,
    'password' => '',
    'timeout' => 0.25,
    'pconnect' => false,
//    'database' => 1,
);
return $redis;
