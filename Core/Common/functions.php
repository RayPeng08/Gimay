<?php
/**
 * Created by PhpStorm.
 * User: 彭泽龙
 * Date: 2016-4-12
 * Time: 11:30
 * 系统函数库
 */

/**
 * 模仿Thinkphp写法
 * @param string $model_name 模型名称
 * @return Gimay\Model 模型
 */
function M($model_name)
{
    return Model($model_name);
}

/**
 * 生产一个model接口，模型在注册树上为单例
 * @param string $model_name 模型名称
 * @param string $db_key 配置文件KEY
 * @return Gimay\Model 模型
 */
function Model($model_name, $db_key = 'master')
{
    return Gimay::getInstance()->model->loadModel($model_name, $db_key);
}

/**
 * 传入一个数据库表，返回一个封装此表的Model接口
 * @param string $table_name 表名
 * @param string $db_key 配置文件KEY
 * @return Gimay\Model 模型
 */
function table($table_name, $db_key = 'master')
{
    return Gimay::getInstance()->model->loadTable($table_name, $db_key);
}

/**
 * 开启会话
 * @param boolean $readonly 事都只读
 */
function session($readonly = false)
{
    Gimay::getInstance()->session->start($readonly);
}

/**
 * 调试数据，终止程序的运行
 */
function debug()
{
    $vars = func_get_args();
    foreach ($vars as $var) {
        if (php_sapi_name() == 'cli') {
            var_export($var);
        } else {
            highlight_string("<?php\n" . var_export($var, true));
            echo '<hr />';
        }
    }
    exit;
}

/**
 * 引发一个错误
 * @param int $error_id 错误代码
 * @param boolean $stop 是否中断系统
 * @return string 错误信息
 */
function E($error_id, $stop = true)
{
    error($error_id, $stop);
}

/**
 * 引发一个错误
 * @param int $error_id 错误代码
 * @param boolean $stop 是否中断系统
 * @return string 错误信息
 */
function error($error_id, $stop = true)
{
    global $php;
    $error = new \Gimay\Error($error_id);
    if (isset($php->error_call[$error_id])) {
        call_user_func($php->error_call[$error_id], $error);
    } elseif ($stop) {
        exit($error);
    } else {
        echo $error;
    }
}

/**
 * 错误信息输出处理
 * @param int $errno 错误代码
 * @param string $errstr 错误信息
 * @param string $errfile 错误文件
 * @param string $errline 错误位置
 */
function error_handler($errno, $errstr, $errfile, $errline)
{
    $info = '';
    switch ($errno) {
        case E_USER_ERROR:
            $level = 'User Error';
            break;
        case E_USER_WARNING:
            $level = 'Warnning';
            break;
        case E_USER_NOTICE:
            $level = 'Notice';
            break;
        default:
            $level = 'Unknow';
            break;
    }

    $title = 'Gimay ' . $level;
    $info .= '<b>File:</b> ' . $errfile . "<br />\n";
    $info .= '<b>Line:</b> ' . $errline . "<br />\n";
    $info .= '<b>Info:</b> ' . $errstr . "<br />\n";
    $info .= '<b>Code:</b> ' . $errno . "<br />\n";
    echo Gimay\Error::info($title, $info, 201);
}

/**
 * 配置文件
 * @param string $key 键名,配置文件名.分组名.参数名,参数是数组还可以一直延伸下去,格式:Auth.timestamp_expires
 * @param boolean $is_sys 是否读取config.ini系统配置
 * @param array $value 设置值
 * @return string/array 配置信息
 */
function C($key, $is_sys = false, $value = '')
{
    global $php;
    $keylist = explode('.', $key);
    if (empty($value)) {
        if ($is_sys) {
            $result = $php->server->config;
        } else {
            /* 此处是用于加载 */
            if (!isset($php->config[$keylist[0]])) {
                $result = $php->config[$keylist[0]];
            }
            $result = $php->config;
        }
        foreach ($keylist as $item) {
            if (isset($result[$item])) {
                $result = $result[$item];
            } else {
                $result = false;
                break;
            }
        }
        return $result;
    } else {
        /* 找不到合适的方法,最多设置5层 */
        if ($is_sys) {
            switch (count($keylist)) {
                case 1:
                    $php->server->config[$keylist[0]] = $value;
                    break;
                case 2:
                    $php->server->config[$keylist[0]][$keylist[1]] = $value;
                    break;
                case 3:
                    $php->server->config[$keylist[0]][$keylist[1]][$keylist[2]] = $value;
                    break;
                case 4:
                    $php->server->config[$keylist[0]][$keylist[1]][$keylist[2]][$keylist[3]] = $value;
                    break;
                case 5:
                    $php->server->config[$keylist[0]][$keylist[1]][$keylist[2]][$keylist[3]][$keylist[4]] = $value;
                    break;
            }
        } else {
            $config = $php->config[$keylist[0]];
            switch (count($keylist)) {
                case 2:
                    $config[$keylist[1]] = $value;
                    break;
                case 3:
                    $config[$keylist[1]][$keylist[2]] = $value;
                    break;
                case 4:
                    $config[$keylist[1]][$keylist[2]][$keylist[3]] = $value;
                    break;
                case 5:
                    $config[$keylist[1]][$keylist[2]][$keylist[3]][$keylist[4]] = $value;
                    break;
            }
            $php->config[$keylist[0]] = $config;
        }
        return true;
    }
}

/**
 * 读取配置文件
 * @param string $ini_file 配置文件路径
 * @return array $config
 */
function LoadIni($ini_file = '')
{
    if (empty($ini_file)) {
        $ini_file = CONFIG_PATH;
    }
    $config = array();
    if (is_file($ini_file)) {
        $config = parse_ini_file($ini_file, true);
    }
    return $config;
}

/**
 * 检查端口是否可以被绑定
 * @param string $host IP
 * @param int $port 端口
 * @param string $errno 错误编码
 * @param string $errstr 错误信息
 * @return boolean true/false
 */
function CheckPortBindable($host = '', $port, &$errno = null, &$errstr = null)
{
    if (empty($host)) {
        $host = GetHostIP();
    }
    $socket = stream_socket_server("tcp://$host:$port", $errno, $errstr);
    if (!$socket) {
        return false;
    }
    fclose($socket);
    unset($socket);
    return true;
}

/**
 * 随机返回一个空闲端口
 * @param string $host IP
 * @param int $port 起始遍历端口
 * @return int $port
 */
function ChoosePort($host = '', $Bport = 9501)
{
    if (empty($host)) {
        $host = GetHostIP();
    }
    $port = 0;
    for ($i = $Bport; $i <= $Bport + 50; $i++) {
        if (CheckPortBindable($host, $i)) {
            $port = $i;
            break;
        }
    }
    return $port;
}

/**
 * 获取本机内/外网IP
 * @return string IP
 */
function GetHostIP($local = true)
{
    $ip = '';
    if ($local) {
        $ip = gethostbyname(getArray($_ENV, 'COMPUTERNAME'));
    } else {
        $ip = getArray($_SERVER, 'REMOTE_ADDR');
    }
    return $ip;
}

/*
 * 处理PHP Notice:  Undefined index问题
 * @param array $array 数组名
 * @param string $str 参数名
 * @return string $val
*/
function getArray($array, $str)
{
    $val = !empty($array[$str]) ? $array[$str] : null;
    return $val;
}