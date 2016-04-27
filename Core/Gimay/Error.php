<?php
/**
 * Created by PhpStorm.
 * User: 彭泽龙
 * Date: 2016-4-12
 * Time: 14:04
 */
namespace Gimay;

/**
 * 错误类
 * 错误输出、数据调试、中断程序运行
 *
 */
class Error extends \Exception
{
    /**
     * 错误ID
     * @var int
     */
    public $error_id;

    /**
     * 错误信息
     * @var string
     */
    public $error_msg;
    static public $error_code;
    static public $stop = false;
    static $echo_html = false;
    static public $display = true;

    /**
     * 错误对象
     * @param $error string 如果为INT，则读取错误信息字典，否则设置错误字符串
     */
    function __construct($error)
    {
        if (is_numeric($error)) {
            if (empty($this->error_id)) {
                include CORE_PATH . 'Data/Text/Error_Code.php';
                //错误ID
                $this->error_id = (int)$error;
                //错误信息
                if (!isset(self::$error_code[$this->error_id])) {
                    $this->error_msg = self::$error_code[$this->error_id];
                    parent::__construct($this->error_msg, $error);
                }
            }
        } else {
            $this->error_id = 0;
            $this->error_msg = $error;
            parent::__construct($error);
        }
        global $php;
        //如果定义了错误监听程序
        if (isset($php->error_call[$this->error_id])) {
            call_user_func($php->error_call[$this->error_id], $error);
        }
        if (self::$stop) {
            exit(Error::info('系统错误', $this->error_msg, 101));
        }
    }

    /**
     * 输出一条错误信息，并结束程序的运行
     * @param $msg
     * @param $content
     * @return string
     */
    static function info($msg, $content, $ecode = 301)
    {
        $data['code'] = $ecode;
        if (empty($msg)) {
            $msg = self::$error_code[$ecode];
        }
        $data['message'] = $msg;
        /* 测试时可以把错误内容打印来,在网页上看到 */
        if (defined('DEBUG') and DEBUG != 'off' and self::$display == true) {
            /*$info = "$msg: $content\n";
            $trace = debug_backtrace();
            $info .= str_repeat('-', 100) . "\n";
            foreach ($trace as $k => $t) {
                if (empty($t['line'])) {
                    $t['line'] = 0;
                }
                if (empty($t['class'])) {
                    $t['class'] = '';
                }
                if (empty($t['type'])) {
                    $t['type'] = '';
                }
                if (empty($t['file'])) {
                    $t['file'] = 'unknow';
                }
                $info .= "#$k line:{$t['line']} call:{$t['class']}{$t['type']}{$t['function']}\tfile:{$t['file']}\n";
            }
            $info .= str_repeat('-', 100) . "\n";*/
            $data['debug'] = "$msg: $content";
        }
        if (!defined('GIMAY_SERVER') and self::$stop) {
            exit(json_encode($data));
        } else {
            return json_encode($data);
        }
    }

    function __toString()
    {
        if (!isset(self::$error_code[$this->error_id])) return '未知名错误.';
        return self::$error_code[$this->error_id];
    }
}
