<?php
/**
 * Created by PhpStorm.
 * User: 彭泽龙
 * Date: 2016-4-13
 * Time: 15:09
 * 缓存制造类，缓存基类
 */
namespace Gimay;
class Cache
{
    public $cache;

    const TYPE_FILE = 0;
    const TYPE_APC = 1;
    const TYPE_XCACHE = 2;
    const TYPE_MEMCACHE = 3;
    const TYPE_EAC = 4;
    const TYPE_DBCACHE = 5;
    const TYPE_WINCACHE = 6;
    const TYPE_SAEMEMCACHE = 7;
    static $backends = array('FileCache', 'ApcCache', 'XCache', 'CMemcache', 'EAcceleratorCache', 'DBCache', 'WinCache', 'SaeMemcache',);

    /**
     * 获取缓存对象
     * @param $scheme
     * @return cache object
     */
    static function create($config)
    {
        if (empty(self::$backends[$config['type']])) return Error::info('缓存创建失败', "缓存组件: {$config['type']} 不存在", 201);
        $backend = "\\Gimay\\Cache\\" . self::$backends[$config['type']];
        return new $backend($config);
    }
}
