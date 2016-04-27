<?php
/**
 * Created by PhpStorm.
 * User: 彭泽龙
 * Date: 2016-4-25
 * Time: 17:50
 * 服务注册与发现配置文件
 */
$srad['master'] = array(
    //'redis_id'               => 'master',                   //redis配置名,默认为'master'
    'modules'            => array('Sns'),               //应用模块列表
    //'heartbeat_check'        => 5,                          //心跳检测频率,默认5秒
    //'heartbeat_time'         => 10,                         //心跳存货时间,默认10秒
);
return $srad;
