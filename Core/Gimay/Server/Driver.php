<?php
/**
 * Created by PhpStorm.
 * User: 彭泽龙
 * Date: 2016-4-12
 * Time: 16:54
 * 驱动接口类
 */
namespace Gimay\Server;

interface Driver
{
    function run($setting);
    function send($client_id, $data);
    function close($client_id);
    function shutdown();
    function setProtocol($protocol);
}