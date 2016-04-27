<?php
/**
 * Created by PhpStorm.
 * User: 彭泽龙
 * Date: 2016-4-14
 * Time: 18:12
 * Redis缓存类
 */
namespace Gimay\Cache;
use Gimay;
class Redis implements Gimay\IFace\Cache
{
    protected $config;
    protected $redis;

    function __construct($config)
    {
        if (empty($config['redis_id']))
        {
            $config['redis_id'] = 'master';
        }
        $this->config = $config;
        $this->redis = \Gimay::$php->redis($config['redis_id']);
    }

    /**
     * 设置缓存
     * @param $key
     * @param $value
     * @param $expire
     * @return bool
     */
    function set($key, $value, $expire = 0)
    {
        if ($expire <= 0)
        {
            $expire = 0x7fffffff;
        }
        return $this->redis->setex($key, $expire, serialize($value));
    }

    /**
     * 获取缓存值
     * @param $key
     * @return mixed
     */
    function get($key)
    {
        return unserialize($this->redis->get($key));
    }

    /**
     * 删除缓存值
     * @param $key
     * @return bool
     */
    function delete($key)
    {
        return $this->redis->del($key);
    }
}
