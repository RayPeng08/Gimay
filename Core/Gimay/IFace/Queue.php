<?php
/**
 * Created by PhpStorm.
 * User: 彭泽龙
 * Date: 2016-4-26
 * Time: 18:10
 * 消息队列接口类
 */
namespace Gimay\IFace;

interface Queue
{
    function push($data);

    function pop();
}