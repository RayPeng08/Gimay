<?php
/**
 * Created by PhpStorm.
 * User: 彭泽龙
 * Date: 2016-4-20
 * Time: 17:43
 * 帐号中心控制器
 */
namespace App\Controller;
use Gimay;
class Passport extends Gimay\Controller
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
   * 登录
   * @param string $account 用户帐号
   * @param string $psw 用户密码
   * @param string $expires 缓存过期时间 默认3600秒
   * @return boolean true/false
   */
    function login(){
        $this->data=$this->iGet;
        $this->data['time']=time();
        return $this->success();
    }
}