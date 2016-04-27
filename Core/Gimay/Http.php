<?php
/**
 * Created by PhpStorm.
 * User: 彭泽龙
 * Date: 2016-4-26
 * Time: 17:56
 * Http处理组件
 */
namespace Gimay;

class Http
{
    static function __callStatic($func, $params)
    {
        return call_user_func_array(array(\Gimay::$php->http, $func), $params);
    }

    static function buildQuery($array)
    {
        if (!is_array($array)) {
            return false;
        }
        $query = array();
        foreach ($array as $k => $v) {
            $query[] = ($k . '=' . urlencode($v));
        }
        return implode("&", $query);
    }
}
