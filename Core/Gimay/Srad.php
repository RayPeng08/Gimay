<?php
/**
 * Created by PhpStorm.
 * User: 彭泽龙
 * Date: 2016-4-25
 * Time: 17:28
 * 服务注册与发现组件
 */
namespace Gimay;
class Srad
{
    const PREFIX = 'srad:';
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
        if (empty($config['heartbeat_check'])) {
            $this->config['heartbeat_check'] = 5;
        }
        if (empty($config['heartbeat_time'])) {
            $this->config['heartbeat_time'] = 10;
        }
        $this->redis = \Gimay::$php->redis($this->config['redis_id']);
    }

    /**
     * 服务注册/心跳刷新状态
     * @param string $HostIP 主机内网IP
     * @param string $OHostIP 主机外网IP
     * @param int $HostPort 主机监听端口
     * @return boolean true/false
     */
    function register($HostIP = '', $OHostIP = '', $HostPort = 9501)
    {
        if (empty($HostIP)) {
            $HostIP = C('server.host', true);
        }
        if (empty($OHostIP)) {
            $OHostIP = C('server.outhost', true);
        }
        if (!empty(C('server.port', true))) {
            $HostPort = (int)C('server.port', true);
        }
        $Modules = $this->config['modules'];
        $expires = $this->config['heartbeat_time'];
        foreach ($Modules as $item) {
            $key = self::PREFIX . strtolower($item);
            $value = array();
            $value['ip'] = $HostIP;
            $value['oip'] = $OHostIP;
            $value['port'] = $HostPort;
            $value['time'] = time();
            $this->redis->set($key, json_encode($value), $expires);
        }
        return true;
    }

    /**
     * 服务发现
     * @param string $Moudle 应用名称
     * @return array $Result
     */
    function find($Moudle)
    {
        $Result = array();
        $key = self::PREFIX . strtolower($Moudle);
        $value = $this->redis->get($key);
        if (!empty($value)) {
            $Result = json_decode($value, true);
        }
        return $Result;
    }

    /**
     * 应用模块检测
     * @param string $Moudle 应用名称
     * @return boolean $Result
     */
    function check($Moudle)
    {
        $Result = true;
        $modules = $this->config['modules'];
        foreach ($modules as $item) {
            if (strtolower($item) == strtolower($Moudle)) {
                $Result = false;
                break;
            }
        }
        return $Result;
    }

    /**
     * 清除服务
     * @param string $key
     * @return bool
     */
    function reset($key)
    {
        $key = self::PREFIX . strtolower($key);
        return $this->redis->del($key);
    }
}
