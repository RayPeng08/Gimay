<?php
/**
 * Created by PhpStorm.
 * User: 彭泽龙
 * Date: 2016-4-23
 * Time: 9:17
 * 频率限制组件
 */
namespace Gimay;
class Limit
{
    const PREFIX = 'limit:';
    protected $config;
    /**
     * @var \redis
     */
    protected $redis;

    /**
     * 构造方法，需要传入一个redis_id
     * @param $config
     */
    function __construct($config)
    {
        $this->config = $config;
        if (empty($config['redis_id'])) {
            $this->config['redis_id'] = 'master';
        }
        if (empty($config['rate'])) {
            $this->config['rate'] = 1000;
        }
        if (empty($config['expire'])) {
            $this->config['expire'] = 1;
        }
        if (empty($config['incrby'])) {
            $this->config['incrby'] = 1;
        }
        $this->redis = \Gimay::$php->redis($this->config['redis_id']);
    }

    /**
     * 增加计数
     * @param $key string 键名
     * @param int $expire 默认1秒
     * @param int $incrby 默认加1
     * @return bool
     */
    function addCount($key, $expire = 0, $incrby = 0)
    {
        if (empty($expire)) {
            $expire = (int)$this->config['expire'];
        }
        if (empty($incrby)) {
            $incrby = (int)$this->config['incrby'];
        }
        $key = self::PREFIX . $key;
        //增加计数
        if ($this->redis->exists($key)) {
            return $this->redis->incr($key, $incrby);
        } //不存在的Key，设置为1
        else {
            return $this->redis->set($key, $incrby, $expire);
        }
    }

    /**
     * 检查是否超过了频率限制，如果超过返回true，未超过返回false
     * @param $key string 键名
     * @param $rate int 频率数
     * @return bool
     */
    function exceed($key, $rate = 0)
    {
        if (empty($rate)) {
            $rate = (int)$this->config['rate'];
        }
        $key = self::PREFIX . $key;
        $count = $this->redis->get($key);

        if (!empty($count) and $count > $rate) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 清除频率计数
     * @param $key
     * @return bool
     */
    function reset($key)
    {
        $key = self::PREFIX . $key;
        return $this->redis->del($key);
    }
}