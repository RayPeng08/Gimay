<?php
/**
 * Created by PhpStorm.
 * User: 彭泽龙
 * Date: 2016-4-12
 * Time: 18:17
 * 协议基类，实现一些公用的方法
 */
namespace Gimay\Protocol;
use Gimay;
abstract class Base implements Gimay\IFace\Protocol
{
    public $default_port;
    public $default_host;
    /**
     * @var \Gimay\IFace\Log
     */
    public $log;

    /**
     * @var \Gimay\Server
     */
    public $server;

    /**
     * @var array
     */
    protected $clients;

    /**
     * 设置Logger
     * @param $log
     */
    function setLogger($log)
    {
        $this->log = $log;
    }

    function run($array=array())
    {
        \Gimay\Error::$echo_html = true;
        $this->server->run($array);
    }

    function daemonize()
    {
        $this->server->daemonize();
    }

    /**
     * 打印Log信息
     * @param $msg
     * @param string $type
     */
    function log($msg)
    {
        $this->log->info($msg);
    }

    function task($task, $dstWorkerId = -1)
    {
        $this->server->task($task, $dstWorkerId = -1);
    }

    function onStart($server)
    {

    }

    function onConnect($server, $client_id, $from_id)
    {

    }

    function onClose($server, $client_id, $from_id)
    {

    }

    function onShutdown($server)
    {

    }
}