<?php
/**
 * Created by PhpStorm.
 * User: 彭泽龙
 * Date: 2016-4-13
 * Time: 15:10
 * Cookie缓存类
 */
namespace Gimay;
class Cookie
{
    public static $path = '/';
    public static $domain = null;
    public static $secure = false;
    public static $httponly = false;

    static function get($key, $default = null)
    {
        if (!isset($_COOKIE[$key])) {
            return $default;
        } else {
            return $_COOKIE[$key];
        }
    }

    static function set($key, $value, $expire = 0)
    {
        if ($expire != 0) {
            $expire = time() + $expire;
        }
        if (defined('GIMAY_SERVER')) {
            \Gimay::$php->http->setcookie($key,
                $value,
                $expire,
                Cookie::$path,
                Cookie::$domain,
                Cookie::$secure,
                Cookie::$httponly);
        } else {
            setcookie($key, $value, $expire, Cookie::$path, Cookie::$domain, Cookie::$secure, Cookie::$httponly);
        }
    }

    static function delete($key)
    {
        unset($_COOKIE[$key]);
        self::set($key, '');
    }
}
