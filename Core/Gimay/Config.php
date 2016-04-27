<?php
/**
 * Created by PhpStorm.
 * User: 彭泽龙
 * Date: 2016-4-12
 * Time: 14:14
 * 配置文件类
 */
namespace Gimay;
class Config extends \ArrayObject
{
    protected $config_path;
    public $dir_num = 0;
    static $debug = false;
    static $active = false;
    public $config;

    /*
     * 设置配置文件目录
     */
    function setPath($dir)
    {
        $this->config_path[] = $dir;
        self::$active = true;
    }

    /*
     * 获取指定序号配置信息
     */
    function offsetGet($index)
    {
        if (!isset($this->config[$index])) {
            $this->load($index);
        }
        return isset($this->config[$index]) ? $this->config[$index] : false;
    }

    /*
    * 读取配置文件
    */
    function load($index)
    {
        foreach ($this->config_path as $path) {
            $filename = $path . '/' . $index . '.php';
            if (is_file($filename)) {
                $retData = include $filename;
                if (empty($retData) and self::$debug) {
                    trigger_error(__CLASS__ . ": $filename 没有配置信息");
                } else {
                    $this->config[$index] = $retData;
                }
            } elseif (self::$debug) {
                trigger_error(__CLASS__ . ": $filename 配置文件不存在");
            }
        }
    }

    /*
     * 设置配置信息
     */
    function offsetSet($index, $newval)
    {
        $this->config[$index] = $newval;
        $data['config'] = $index;
        $data['value'] = $newval;
        echo json_encode($data);
    }

    /*
     * 销毁配置信息
     */
    function offsetUnset($index)
    {
        unset($this->config[$index]);
    }

    /*
     * 判断是否存在配置信息
     */
    function offsetExists($index)
    {
        return isset($this->config[$index]);
    }
}