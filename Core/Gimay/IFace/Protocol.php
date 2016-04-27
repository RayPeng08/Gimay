<?php
/**
 * Created by PhpStorm.
 * User: 彭泽龙
 * Date: 2016-4-12
 * Time: 16:41
 * 通讯协议接口类
 */
namespace Gimay\IFace;

interface Protocol
{
    function onStart($server);
    function onConnect($server, $client_id, $from_id);
    function onReceive($server,$client_id, $from_id, $data);
    function onClose($server, $client_id, $from_id);
    function onShutdown($server);
}
