<?php
/**
 * Created by PhpStorm.
 * User: 彭泽龙
 * Date: 2016-4-12
 * Time: 15:29
 */
namespace Gimay\Protocol;

use App\Common;
use Gimay;

abstract class WebServer extends Base
{
    const SOFTWARE = "Gimay";
    const POST_MAXSIZE = 2000000; //POST最大2M
    const DEFAULT_PORT = 9501;
    const DEFAULT_HOST = '0.0.0.0';

    public $config = array();

    /**
     * @var \Gimay\Http\Parser
     */
    protected $parser;

    protected $mime_types;
    protected $static_dir;
    protected $static_ext;
    protected $dynamic_ext;
    protected $document_root;
    protected $deny_dir;

    protected $keepalive = false;
    protected $gzip = false;
    protected $expire = false;

    /**
     * @var \Gimay\Request;
     */
    public $currentRequest;
    /**
     * @var \Gimay\Response;
     */
    public $currentResponse;

    public $requests = array(); //保存请求信息,里面全部是Request对象

    function __construct($config = array())
    {
        define('GIMAY_SERVER', true);
        Gimay\Error::$echo_html = true;
    }

    function setDocumentRoot($path)
    {
        $this->document_root = $path;
    }

    /**
     * 设置应用路径，仅对AppServer和AppFPM有效
     * @param $path
     */
    function setAppPath($path)
    {
        $this->apps_path = $path;
    }

    /**
     * 得到请求对象
     * @param $fd
     * @return Gimay\Request
     */
    function getRequest($fd)
    {
        return $this->requests[$fd];
    }

    /**
     * 从ini文件中加载配置
     * @param $ini_file
     */
    function loadSetting($ini_file)
    {
        if (!is_file($ini_file)) exit("配置文件错误($ini_file)\n");
        $config = parse_ini_file($ini_file, true);
        /*--------------Server------------------*/
        //开启http keepalive
        if (!empty($config['server']['keepalive'])) {
            $this->keepalive = true;
        }
        //是否压缩
        if (!empty($config['server']['gzip_open']) and function_exists('gzdeflate')) {
            $this->gzip = true;
            //default level
            if (empty($config['server']['gzip_level'])) {
                $config['server']['gzip_level'] = 1;
            } //level [1, 9]
            elseif ($config['server']['gzip_level'] > 9) {
                $config['server']['gzip_level'] = 9;
            }
        }
        //过期控制
        if (!empty($config['server']['expire_open'])) {
            $this->expire = true;
            if (empty($config['server']['expire_time'])) {
                $config['server']['expire_time'] = 60;
            }
        }
        /*--------------Session------------------*/
        if (empty($config['session']['cookie_life'])) $config['session']['cookie_life'] = 86400; //保存SESSION_ID的cookie存活时间
        if (empty($config['session']['session_life'])) $config['session']['session_life'] = 1800; //Session在Cache中的存活时间
        /*--------------Apps------------------*/
        if (empty($config['apps']['auto_reload'])) $config['apps']['auto_reload'] = 0;

        if (!empty($config['access']['post_maxsize'])) {
            $this->config['server']['post_maxsize'] = $config['access']['post_maxsize'];
        }
        if (empty($config['server']['post_maxsize'])) {
            $config['server']['post_maxsize'] = self::POST_MAXSIZE;
        }
        /*--------------Access------------------*/
        $this->deny_dir = array_flip(explode(',', $config['access']['deny_dir']));
        $this->static_dir = array_flip(explode(',', $config['access']['static_dir']));
        $this->static_ext = array_flip(explode(',', $config['access']['static_ext']));
        $this->dynamic_ext = array_flip(explode(',', $config['access']['dynamic_ext']));
        /*--------------document_root------------*/
        $this->document_root = WEB_PATH;
        /*-----merge----*/
        if (!is_array($this->config)) {
            $this->config = array();
        }
        $this->config = array_merge($this->config, $config);
    }

    static function create($ini_file = null)
    {
        $opt = getopt('h:p:d:');
        //daemonize
        if (empty($opt['d'])) {
            $opt['d'] = false;
        }
        $protocol = new Gimay\Protocol\AppServer();
        if ($ini_file) {
            $protocol->loadSetting($ini_file); //加载配置文件
        }
        //port
        if (!empty($opt['p'])) {
            $protocol->default_port = $opt['p'];
        } elseif (!empty($protocol->config['server']['port'])) {
            $protocol->default_port = $protocol->config['server']['port'];
        } else {
            $protocol->default_port = self::DEFAULT_PORT;
        }
        //host
        if (!empty($opt['h'])) {
            $protocol->default_host = $opt['h'];
        } elseif (!empty($protocol->config['server']['host'])) {
            $protocol->default_host = $protocol->config['server']['host'];
        } else {
            $protocol->default_host = self::DEFAULT_HOST;
        }
        $server_host = $protocol->default_host;
        $server_port = $protocol->default_port;
        /*if ($server_host == '0.0.0.0' || $server_host == '127.0.0.1') {
            $server_host = GetHostIP();
        }*/

        //检测返回真正的端口,此方法主要是针对同台服务器多开的情况
        /*$server_port = ChoosePort($server_host, $server_port);
        if ($server_port != 0) {
            $protocol->default_host = $server_port;
        }*/
        //服务注册
        global $php;
        if (!empty($protocol->config['server']['is_srad']) && !empty(C('Srad.' . $php->factory_key . '.modules'))) {
            $php->Srad->register($server_host, '', $server_port);
        }

        $server = Gimay\Network\Server::autoCreate($protocol->default_host, $protocol->default_port);
        $server->setProtocol($protocol);

        if (!empty($protocol->config['server']['worker_num'])) {
            $server->runtimeSetting['worker_num'] = $protocol->config['server']['worker_num'];
        }

        return $protocol;
    }
}