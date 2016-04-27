<?php
/**
 * Created by PhpStorm.
 * User: 彭泽龙
 * Date: 2016-4-23
 * Time: 10:00
 * 频率配置文件
 */
$limit['master'] = array(
    //'redis_id'      => 'master',                   //redis配置名,默认master
    'rate'         => 100000,                        //频率数
    //'expire'        => 1,                          //频率时间,默认1秒
    //'incrby'        => 1,                          //步增值,默认为1
);
return $limit;