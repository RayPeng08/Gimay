<?php
/**
 * Created by PhpStorm.
 * User: 彭泽龙
 * Date: 2016-4-13
 * Time: 15:12
 * Http处理类
 */
namespace Gimay\Http;

use Gimay;

class PWS implements \Gimay\IFace\Http
{
    function header($k, $v)
    {
        $k = ucwords($k);
        \Gimay::$php->response->setHeader($k, $v);
    }

    function status($code)
    {
        \Gimay::$php->response->setHttpStatus($code);
    }

    function response($content)
    {
        $this->finish($content);
    }

    function redirect($url, $mode = 301)
    {
        \Gimay::$php->response->setHttpStatus($mode);
        \Gimay::$php->response->setHeader('Location', $url);
    }

    function finish($content = null)
    {
        \Gimay::$php->request->finish = 1;
        if ($content) \Gimay::$php->response->body = $content;
        throw new Gimay\ResponseException;
    }

    function setcookie($name, $value = null, $expire = null, $path = '/', $domain = null, $secure = null, $httponly = null)
    {
        \Gimay::$php->response->setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }

    function getRequestBody()
    {
        return \Gimay::$php->request->body;
    }
}
