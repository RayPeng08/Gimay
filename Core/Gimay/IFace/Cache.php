<?php
/**
 * Created by PhpStorm.
 * User: 彭泽龙
 * Date: 2016-4-13
 * Time: 15:05
 * 缓存接口类
 */
namespace Gimay\IFace;
interface Cache
{
    /**
     * 设置缓存
     * @param $key
     * @param $value
     * @param $expire
     * @return bool
     */
    function set($key,$value,$expire=0);
    /**
     * 获取缓存值
     * @param $key
     * @return mixed
     */
    function get($key);
    /**
     * 删除缓存值
     * @param $key
     * @return bool
     */
    function delete($key);
}