<?php
/**
 * Created by PhpStorm.
 * User: 彭泽龙
 * Date: 2016-4-13
 * Time: 15:25
 * Controller的基类，控制器基类
 */
namespace Gimay;
class Controller extends Object
{
    public $jsonp = '';

    /**
     * 是否对GET/POST/REQUEST/COOKIE参数进行转意
     * @var bool
     */
    protected $data = array();
    protected $model;
    protected $config;

    protected $iGet = array();
    protected $iPost = array();

    function __construct(\Gimay $gimay)
    {
        $this->gimay = $gimay;
        $this->model = $gimay->model;
        $this->config = $gimay->config;
        $this->iGet = $gimay->request->get;
        $this->iPost = $gimay->request->post;
        if (!empty($this->_get('jsonp'))) {
            $this->jsonp = $this->_get('jsonp');
        }
        $gimay->__init();
    }

    /**
     * 输出JSON字串
     * @param string $msg 信息
     * @return string
     */
    function success($msg = '')
    {
        return $this->error(0, $msg);
    }

    /**
     * 输出JSON字串
     * @param string $code 错误代码
     * @param string $msg 错误信息,默认空则按错误代码内容输出
     * @return string
     */
    function error($code = 0, $msg = '')
    {
        if (empty($msg) && $code != 0) {
            $msg = Error::$error_code[$code];
        }
        $json['code'] = $code;
        $json['message'] = $msg;
        if (!empty($this->data)) {
            $json['data'] = $this->data;
        }
        return $json;
    }

    /**
     * 添加变量
     * @param $name string  变量/变量名
     * @param $value string 变量值
     */
    function assign($name, $value = '')
    {
        if (is_array($name)) {
            $this->data = array_merge($this->data, $name);
        } else if (is_object($name)) {
            foreach ($name as $key => $val) {
                $this->data[$key] = $val;
            }
        } else {
            $this->data[$name] = $value;
        }
    }

    /**
     * 避免提示undefined index
     */
    function _server($str)
    {
        $val = getArray($_SERVER, $str);
        return $val;
    }

    /**
     * 避免提示undefined index
     */
    function _get($str)
    {
        $val = getArray($this->iGet, $str);
        return $val;
    }

    /**
     * 避免提示undefined index
     */
    function _post($str)
    {
        $val = getArray($this->iPost, $str);
        return $val;
    }

    function __destruct()
    {
        $this->gimay->__clean();
    }
}
