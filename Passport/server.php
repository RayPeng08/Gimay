<?php
/**
 * 服务端入口
 * User: 彭泽龙
 * Date: 2016-4-2
 * Time: 14:27
 */

// 应用入口文件
error_reporting ( E_ERROR );
date_default_timezone_set ( 'PRC' );
// 检测PHP环境
if(version_compare(PHP_VERSION,'5.3.0','<'))  die('require PHP > 5.3.0 !');

define('DEBUG', 'off');
//PHP程序的根目录
define('WEB_PATH', realpath(__DIR__));
define('CONFIG_PATH', WEB_PATH.'/Conf/Config.ini');
define('APP_PATH', WEB_PATH.'/Apps/');
define('CORE_PATH', realpath(__DIR__ . '/../').'/Core/');
//包含框架入口文件
require CORE_PATH . 'Config.php';

/**
 * 显示Usage界面
 * php server.php start|stop|reload
 */
Gimay\Network\Server::start(function ()
{
    $server = Gimay\Protocol\WebServer::create(CONFIG_PATH);
    $server->setAppPath(APP_PATH);                                 //设置应用所在的目录
    $server->setDocumentRoot(WEB_PATH);
    $server->setLogger(\Gimay::getInstance()->log); //Logger
    //$server->daemonize();                                                  //作为守护进程
    $server->run();
});
