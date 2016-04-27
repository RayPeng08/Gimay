<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016-4-12
 * Time: 17:12
 * 日志接口
 */
namespace Gimay\IFace;
use Gimay;
interface Log
{
    /**
     * 写入日志
     *
     * @param $msg   string 内容
     * @param $type  int 类型
     */
    function put($msg, $type = Gimay\Log::INFO);
}