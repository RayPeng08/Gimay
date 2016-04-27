<?php
/**
 * Created by PhpStorm.
 * User: 彭泽龙
 * Date: 2016-4-13
 * Time: 14:42
 * Http接口类
 */
namespace Gimay\IFace;
interface Http
{
    function header($k, $v);

    function status($code);

    function response($content);

    function redirect($url, $mode = 301);

    function finish($content = null);

    function setcookie($name, $value = null, $expire = null, $path = '/', $domain = null, $secure = null,
                       $httponly = null);
}