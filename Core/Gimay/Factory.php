<?php
/**
 * Created by PhpStorm.
 * User: 彭泽龙
 * Date: 2016-4-13
 * Time: 16:43
 * 构造函数类
 */
namespace Gimay;

use Gimay\Exception\NotFound;

/**
 * Class Factory
 * @method static getCache
 */
class Factory
{
    public static function __callStatic($func, $params)
    {
        $resource_id = empty($params[0]) ? 'master' : $params[0];
        $resource_type = strtolower(substr($func, 3));

        if (empty(C($resource_type . '.' . $resource_id))) {
            throw new NotFound(__CLASS__ . ": 组件[{$resource_type}/{$resource_id}]没找到.");
        }
        $config = C($resource_type . '.' . $resource_id);
        $class = '\\Gimay\\' . ucfirst($resource_type) . '\\' . $config['type'];
        return new $class($config);
    }
}
