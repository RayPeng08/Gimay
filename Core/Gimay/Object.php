<?php
/**
 * Created by PhpStorm.
 * User: 彭泽龙
 * Date: 2016-4-13
 * Time: 15:23
 * 应用类的基类
 */
namespace Gimay;
class Object
{
    /**
     * @var \Gimay
     */
    public $gimay;

    function __get($key)
    {
        return $this->gimay->$key;
    }

    function __call($func, $param)
    {
        return call_user_func_array(array($this->gimay, $func), $param);
    }
}