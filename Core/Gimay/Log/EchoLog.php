<?php
/**
 * Created by PhpStorm.
 * User: 彭泽龙
 * Date: 2016-4-12
 * Time: 17:14
 * 日志操作类
 */
namespace Gimay\Log;

use Gimay;

class EchoLog extends Gimay\Log implements Gimay\IFace\Log
{
    protected $display = true;

    function __construct($config)
    {
        if (isset($config['display']) and $config['display'] == false) {
            $this->display = false;
        }
        parent::__construct($config);
    }

    function put($msg, $level = self::INFO)
    {
        if ($this->display) {
            $log = $this->format($msg, $level);
            if ($log) echo $log;
        }
    }
}