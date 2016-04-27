<?php
/**
 * Created by PhpStorm.
 * User: 彭泽龙
 * Date: 2016-4-14
 * Time: 18:13
 * Memcache封装类，支持memcache和memcached两种扩展
 */
namespace Gimay\Cache;
class SaeMemcache implements \Gimay\IFace\Cache
{
    public $multi = false;
    //启用压缩
    static $compress = MEMCACHE_COMPRESSED;
    public $cache;

    function __construct($configs)
    {
        $this->cache = \memcache_init();
        if($this->cache === false)
        {
            throw new \Exception("SaeMemcachec初始化失败!", 1);
        }
    }
    /**
     * 获取数据
     * @see libs/system/ICache#get($key)
     */
    function get($key)
    {
        return $this->cache->get($key);
    }
    function set($key, $value, $expire=0)
    {
        return $this->cache->set($key, $value, self::$compress, $expire);
    }
    function delete($key)
    {
        return $this->cache->delete($key);
    }
}