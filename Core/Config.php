<?php
/**
 * User: 彭泽龙
 * Date: 2016-4-2
 * Time: 18:01
 * 构造函数，全局对象$php的构造
 */
if (PHP_OS == 'WINNT') {
    define("NL", "\r\n");
} else {
    define("NL", "\n");
}
define("BL", "<br />" . NL);
define('GIMAY_SERVER', true);
require_once CORE_PATH . '/Common/functions.php';
require_once CORE_PATH . '/Gimay/Gimay.php';
/**
 * 注册顶层命名空间到自动载入器
 */
Gimay\Loader::addNameSpace('Gimay', CORE_PATH . '/Gimay');
spl_autoload_register('\\Gimay\\Loader::autoload');

/**
 * 产生类库的全局变量
 */
global $php;
$php = Gimay::getInstance();
//设置PID文件的存储路径
Gimay\Network\Server::setPidFile(WEB_PATH . '/Logs/server.pid');
$php->test += 1;
