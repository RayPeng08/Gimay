<?php
/**
 * Created by PhpStorm.
 * User: 彭泽龙
 * Date: 2016-4-12
 * Time: 10:07
 */
namespace Gimay;

/**
 * Gimay库加载器
 *
 */
class Loader
{
    /**
     * 命名空间的路径
     */
    protected static $namespaces;
    static $gimay;
    static $_objects;

    function __construct(\Gimay $gimay)
    {
        self::$gimay = $gimay;
        self::$_objects = array(
            'model' => new \ArrayObject,
            'object' => new \ArrayObject
        );
    }

    /**
     * for composer
     */
    static function vendor_init()
    {
        require __DIR__ . '/../Config.php';
    }

    /**
     * 加载一个模型对象
     * @param $model_name string 模型名称
     * @return $model_object 模型对象
     */
    static function loadModel($model_name)
    {
        if (isset(self::$_objects['model'][$model_name])) {
            return self::$_objects['model'][$model_name];
        } else {
            $model_file = APP_PATH . '/Models/' . $model_name . '.php';
            if (!file_exists($model_file)) {
                Error::info('模型不存在!', "模型 '$model_name' 不存在", 401);
            }
            require($model_file);
            self::$_objects['model'][$model_name] = new $model_name(self::$gimay);
            return self::$_objects['model'][$model_name];
        }
    }

    /**
     * 自动载入类
     * @param $class
     */
    static function autoload($class)
    {
        $root = explode('\\', trim($class, '\\'), 2);
        if (count($root) > 1 and isset(self::$namespaces[$root[0]])) {
            include self::$namespaces[$root[0]] . '/' . str_replace('\\', '/', $root[1]) . '.php';
        }
    }

    /**
     * 设置根命名空间
     * @param $root
     * @param $path
     */
    static function addNameSpace($root, $path)
    {
        self::$namespaces[$root] = $path;
    }
}