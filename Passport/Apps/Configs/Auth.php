<?php
/**
 * Created by PhpStorm.
 * User: 彭泽龙
 * Date: 2016-4-25
 * Time: 18:23
 * 验证主键配置文件
 */
$auth['master'] = array(
    //'redis_id'                 => 'master',                   //redis配置名,默认master
    //'access_token_expires'     => 3600,                       //通行令牌有效期,默认3600秒
    'timestamp_expires'        => 50,                         //时间戳有效期,默认5秒
);
return $auth;