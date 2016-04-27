<?php
/**
 * Created by PhpStorm.
 * User: 彭泽龙
 * Date: 2016-4-13
 * Time: 16:22
 * 日志配置文件
 */
$log['master'] = array(
    'type'         => 'EchoLog',                       //FileLog日志保存成文件,EchoLog日志直接打印在控制台
    //'file'         => WEB_PATH . '/Logs/Server.log',   //日志文件名称,与按日期存储相斥
    //'level'        => 1,                               //错误日志等级0-4
    //'date'         => true,                            //是否按日期存储
    //'dir'          => WEB_PATH.'/Logs/',               //存储目录,按日期存储需要填
    //'verbose'      => false,                           //记录更详细的信息（目前记多了文件名、行号）
    //'enable_cache' => false                            //是否开启缓存区
);
return $log;