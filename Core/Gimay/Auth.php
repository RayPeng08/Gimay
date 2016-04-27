<?php
/**
 * Created by PhpStorm.
 * User: 彭泽龙
 * Date: 2016-4-25
 * Time: 18:02
 * 验证组件
 */
namespace Gimay;
class Auth
{
    const PREFIX = 'auth:';
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
        if (empty($config['access_token_expires'])) {
            $this->config['access_token_expires'] = 3600;
        }
        if (empty($config['timestamp_expires'])) {
            $this->config['timestamp_expires'] = 5;
        }
        $this->redis = \Gimay::$php->redis($this->config['redis_id']);
    }

    /*
    * 查询是否存在
    * @param string $appid APPID
    * @param string $appsecret AppSecret
    * @return array access_token信息
    */
    function exists($appid, $appsecret)
    {
        $Result = array();
        $key = self::PREFIX . $appid . $appsecret;
        $access_token = $this->redis->get($key);
        //续期
        if (!empty($access_token)) {
            $expires = (int)$this->config['access_token_expires'];
            $this->redis->expire($key, $expires);
            $this->redis->expire($access_token, $expires);

            $Result['access_token'] = $access_token;
            $Result['expires'] = $expires;
        }
        return $Result;
    }

    /*
    * 获取AcessToken令牌
    * @param string $appid APPID
    * @param string $appsecret AppSecret
    * @param array $error 错误信息，错误代码，错误信息
    * @param int isrefresh 是否刷新
    * @return array access_token信息
    */
    function getAccessToken($appid, $appsecret, &$error = array(), $isrefresh = 0)
    {
        $Result = array();
        if (empty($isrefresh)) {
            $Result = $this->exists($appid, $appsecret);
        }
        if (empty($Result)) {
            $app = Model('App')->findByAppID($appid);
            if (empty($app)) {
                $error['code'] = 304;
                $error['message'] = 'APPID不存在';
            }
            if ($app['appsecret'] != $appsecret) {
                $error['code'] = 304;
                $error['message'] = 'APPSecret不正确';
            }
            if ($app['status'] != 1) {
                $error['code'] = 305;
                $error['message'] = '该APPID已禁用';
            }
            if (empty($error)) {
                $key = self::PREFIX . $appid . $appsecret;
                $expires = (int)$this->config['access_token_expires'];
                $timestamp = time();
                $access_token = sha1($appid . $appsecret . $timestamp);
                $value['appid'] = $appid;
                $value['appsecret'] = $appsecret;
                $this->redis->set($key, $access_token, $expires);
                $this->redis->set($access_token, json_encode($value), $expires);

                $Result['access_token'] = $access_token;
                $Result['expires'] = $expires;
            }
        }
        return $Result;
    }

    /*
    * 获取access_token信息
    * @param string $access_token
    * @return array appid,appsecret
    */
    function getTokenInfo($access_token)
    {
        $Result = array();
        $value = $this->redis->get($access_token);
        if (!empty($value)) {
            $Result = json_decode($value, true);
        }
        return $Result;
    }

    /*
    * 检测签名是否正确
    * @param \Gimay $gimay  对象
    * @return array $Result 错误信息和详细信息
    */
    function checkSign(\Gimay $gimay)
    {
        //获取参数
        $iRequest = $gimay->request;
        $iGet = $iRequest->get;
        $access_token = getArray($iGet, 'access_token');
        $Sign = getArray($iGet, 'sign');
        $Timestamp = (int)getArray($iGet, 'timestamp');
        $Result = array();
        if (empty($access_token)) {
            $Result['message'] = 'access_token不能为空!';
        } else if (empty($this->getTokenInfo($access_token))) {
            $Result['message'] = 'access_token已过期或无效!';
        } else if (empty($Sign)) {
            $Result['message'] = '签名不能为空!';
        } else if (empty($Timestamp) || !is_numeric($Timestamp)) {
            $Result['message'] = '时间戳不能为空或无效!';
        } else {
            $expires = $this->config['timestamp_expires'];
            if (time() - $Timestamp > $expires) {
                $Result['message'] = '时间戳已过期!';
            } else {
                $sSign = $this->getSign($gimay);
                if ($sSign != $Sign) {
                    $Result['message'] = '签名不正确!';
                }
            }
        }
        if (isset($Result['message'])) {
            $Result['info']['old'] = $Timestamp;
            $Result['info']['now'] = time();
            $Result['info']['sign'] = $sSign;
            $Result['info']['get'] = $iGet;
        }
        return $Result;
    }

    /*
    * 获取签名
    * @param \Gimay $gimay  对象
    * @return string $Sign 签名
    */
    function getSign(\Gimay $gimay)
    {
        //获取参数
        $iRequest = $gimay->request;
        $path = $iRequest->meta['path'];
        $iGet = $iRequest->get;
        $iPost = $iRequest->post;
        $access_token = getArray($iGet, 'access_token');
        $App = $this->getTokenInfo($access_token);

        //去掉前面的/号
        $path = substr($path, 1, strlen($path) - 1);

        //将除sign参数以外的参数进行汇总,再加上appsecret
        $paramArray[0] = 'path' . $path;
        if (!empty($App)) {
            $paramArray[1] = 'appsecret' . $App['appsecret'];
        }
        foreach ($iGet as $Key => $value) {
            if ($Key != 'sign') {
                $paramArray[] = $Key . $value;
            }
        }
        foreach ($iPost as $Key => $value) {
            $paramArray[] = $Key . $value;
        }

        //进行冒泡排序
        $bubbleArray = $this->bubbleSort($paramArray);

        //拼凑成为一个字符串
        $encryptStr = '';
        foreach ($bubbleArray as $item) {
            $encryptStr .= $item;
        }

        //sha1加密
        $Sign = sha1($encryptStr);
        return $Sign;
    }

    /*
    * 冒泡排序法(从小到大)
    * @param array $array 数组
    * @return array $array 排序号数组
    */
    function bubbleSort($array)
    {
        $count = count($array);
        if ($count <= 0) {
            return false;
        }
        for ($i = 0; $i < $count; $i++) {
            for ($k = $count - 1; $k > $i; $k--) {
                if ($array[$k] < $array[$k - 1]) {
                    $tmp = $array[$k];
                    $array[$k] = $array[$k - 1];
                    $array[$k - 1] = $tmp;
                }
            }
        }
        return $array;
    }

    /**
     * 清除频率计数
     * @param string $access_token
     * @return bool
     */
    function reset($access_token)
    {
        $info = $this->getTokenInfo($access_token);
        if (!empty($info)) {
            $this->redis->del(self::PREFIX . $info['appid']);
        }
        return $this->redis->del($access_token);
    }
}
