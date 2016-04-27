<?php
/**
 * Created by PhpStorm.
 * User: 彭泽龙
 * Date: 2016-4-13
 * Time: 16:25
 * 验证控制器
 */
namespace App\Controller;

use Gimay;

class Sns extends Gimay\Controller
{
    function __construct($gimay)
    {
        parent::__construct($gimay);
    }

    function index()
    {
        return $this->error(301, '接口不存在!');
    }

    /*
    * 获取AcessToken令牌
    * @param string $appid APPID
    * @param string $appsecret AppSecret
    * @param string $expires 缓存过期时间 默认3600秒
    * @return array access_token信息
    */
    function oauth()
    {
        $appid = $this->_get('appid');
        $appsecret = $this->_get('appsecret');
        $isReFresh = !empty($this->_get('refresh')) ? $this->_get('refresh') : 0;
        if (empty($appid) || empty($appsecret)) {
            return $this->error(304, 'APPID或APPSecret不能为空!');
        }
        $error = array();
        $access_token = $this->auth->GetAccessToken($appid, $appsecret, $error, $isReFresh);
        if (empty($access_token)) {
            return $this->error($error['code'], $error['message']);
        }
        $this->data = $access_token;
        return $this->success();
    }
}