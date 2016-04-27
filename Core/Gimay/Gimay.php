<?php
/**
 * Created by PhpStorm.
 * User: 彭泽龙
 * Date: 2016-4-12
 * Time: 9:46
 */

//加载核心的文件
require_once __DIR__ . '/Loader.php';
require_once __DIR__ . '/ModelLoader.php';

use Gimay\Exception\NotFound;

/**
 * 系统核心类，外部使用全局变量$php引用
 * 核心类，提供一个Gimay对象引用树和基础的调用功能
 *
 * @property \Gimay\Srad              $srad
 * @property \Gimay\Auth              $auth
 * @property \Gimay\Database          $db
 * @property \Gimay\IFace\Cache       $cache
 * @property \Gimay\Upload            $upload
 * @property \Gimay\Event             $event
 * @property \Gimay\Http\PWS          $http
 * @property \Gimay\Session           $session
 * @property \redis                   $redis
 * @property \Gimay\Config            $config
 * @property \Gimay\Log               $log
 * @property \Gimay\Limit             $limit
 */
class Gimay
{
    /**
     * @var Gimay\Protocol\HttpServer
     */
    public $server;
    public $protocol;

    /*
     * @var Gimay\Client\AppClient
     */
    public $client;
    public $is_runclient = false;

    /**
     * @var Gimay\Request
     */
    public $request;

    public $config;//配置文件列表,读取APPs/Configs里的配置文件

    /**
     * @var Gimay\Response
     */
    public $response;
    static public $app_path;
    static public $controller_path = '';

    /**
     * 可使用的组件
     */
    static $modules = array(
        'redis' => true,    //redis
        'db' => true,       //数据库
        'cache' => true,    //缓存
        //'event' => true,    //消息队列
        'log' => true,      //日志
        //'upload' => true,   //上传组件
        'session' => true,  //session
        //'http' => true,     //http
        'limit' => true,    //频率限制组件
        'srad' => true,     //服务注册与发现组件
        'auth' => true,     //验证组件
    );

    /**
     * 允许多实例的模块
     * @var array
     */
    static $multi_instance = array(
        'cache' => true,
        'db' => true,
        'redis' => true,
        'log' => true,
    );

    static $default_controller = array('controller' => 'Sns', 'view' => 'index');

    static $charset = 'utf-8';
    static $debug = false;

    static $setting = array();
    public $error_call = array();
    /**
     * Gimay类的实例
     * @var Gimay
     */
    static public $php;

    /**
     * 对象池
     * @var array
     */
    protected $objects = array();

    /**
     * 传给factory
     */
    public $factory_key = 'master';

    /**
     * 发生错误时的回调函数
     */
    public $error_callback;

    public $load;

    /**
     * @var \Gimay\ModelLoader
     */
    public $model;
    public $env;//环境参数

    protected $hooks = array();
    protected $router_function;

    const HOOK_INIT = 1; //初始化
    const HOOK_ROUTE = 2; //URL路由
    const HOOK_CLEAN = 3; //清理

    /**
     * 测试数据
     */
    public $test = 0;

    private function __construct()
    {
        if (!defined('DEBUG')) define('DEBUG', 'on');

        $this->env['sapi_name'] = php_sapi_name();
        //查看当前SAPI接口类型
        if ($this->env['sapi_name'] != 'cli') {
            Gimay\Error::$echo_html = false;
        }

        if (empty(self::$app_path)) {
            if (defined('APP_PATH')) {
                self::$app_path = APP_PATH;
            } else {
                Gimay\Error::info("接口目录不存在!", __CLASS__ . ": 应用接口目录未指定!", 201);
            }
        }

        //将此目录作为App命名空间的根目录
        Gimay\Loader::addNameSpace('App', self::$app_path . '/Classes');

        $this->load = new Gimay\Loader($this);
        $this->model = new Gimay\ModelLoader($this);
        $this->config = new Gimay\Config;
        $this->config->setPath(self::$app_path . '/Configs');
        $this->server->config = LoadIni();//初始化配置文件
        $this::$default_controller = C('ReWrite.0.mvc');//默认路由地址

        //路由钩子，URLRewrite,路由地址重写
        $this->addHook(Gimay::HOOK_ROUTE, 'urlrouter_rewrite');
        //mvc
        $this->addHook(Gimay::HOOK_ROUTE, 'urlrouter_mvc');
        //设置路由函数
        $this->router(array($this, 'urlRoute'));
    }

    /**
     * 初始化
     * @return Gimay
     */
    static function getInstance()
    {
        if (!self::$php) {
            self::$php = new Gimay;
        }
        return self::$php;
    }

    /**
     * 获取资源消耗
     * @return array
     */
    function runtime()
    {
        // 显示运行时间
        $return['time'] = number_format((microtime(true) - $this->env['runtime']['start']), 4) . 's';

        $startMem = array_sum(explode(' ', $this->env['runtime']['mem']));
        $endMem = array_sum(explode(' ', memory_get_usage()));
        $return['memory'] = number_format(($endMem - $startMem) / 1024) . 'kb';
        return $return;
    }

    /**
     * 压缩内容
     * @return null
     */
    function gzip()
    {
        //不要在文件中加入UTF-8 BOM头
        //ob_end_clean();
        ob_start("ob_gzhandler");
        #是否开启压缩
        if (function_exists('ob_gzhandler')) {
            ob_start('ob_gzhandler');
        } else {
            ob_start();
        }
    }

    /**
     * 初始化环境
     * @return null
     */
    function __init()
    {
        #DEBUG
        if (defined('DEBUG') and DEBUG == 'on') {
            #捕获错误信息
//            set_error_handler('error_handler');
            #记录运行时间和内存占用情况
            $this->env['runtime']['start'] = microtime(true);
            $this->env['runtime']['mem'] = memory_get_usage();
        }
        $this->callHook(self::HOOK_INIT);
    }

    /**
     * 执行Hook函数列表
     * @param $type
     */
    protected function callHook($type)
    {
        if (isset($this->hooks[$type])) {
            foreach ($this->hooks[$type] as $f) {
                if (!is_callable($f)) {
                    trigger_error("钩子函数[$f]出现异常.");
                    continue;
                }
                $f($this);
            }
        }
    }

    /**
     * 清理
     */
    function __clean()
    {
        $this->env['runtime'] = array();
        $this->callHook(self::HOOK_CLEAN);
    }

    /**
     * 增加钩子函数
     * @param $type
     * @param $func
     */
    function addHook($type, $func)
    {
        $this->hooks[$type][] = $func;
    }

    function __get($lib_name)
    {
        //如果不存在此对象，从工厂中创建一个
        if (empty($this->$lib_name)) {
            //载入组件
            $this->$lib_name = $this->loadModule($lib_name);
        }
        return $this->$lib_name;
    }

    /**
     * 加载内置的Gimay模块
     * @param $module
     * @param $key
     * @return mixed
     */
    protected function loadModule($module, $key = 'master')
    {
        $object_id = $module . '_' . $key;
        if (empty($this->objects[$object_id])) {
            $this->factory_key = $key;
            $user_factory_file = self::$app_path . '/Factory/' . $module . '.php';
            //尝试从用户工厂构建对象
            if (is_file($user_factory_file)) {
                $object = require $user_factory_file;
            } //系统默认
            else {
                $system_factory_file = CORE_PATH . '/Factory/' . $module . '.php';
                //组件不存在，抛出异常
                if (!is_file($system_factory_file)) {
                    throw new NotFound("组件[$module]找不到.");
                }
                $object = require $system_factory_file;
            }
            $this->objects[$object_id] = $object;
        }
        return $this->objects[$object_id];
    }

    function __call($func, $param)
    {
        //built-in module
        if (isset(self::$multi_instance[$func])) {
            if (empty($param[0]) or !is_string($param[0])) {
                throw new Exception("组件名称不能为空.");
            }
            return $this->loadModule($func, $param[0]);
        } //尝试加载用户定义的工厂类文件
        elseif (is_file(self::$app_path . '/Factory/' . $func . '.php')) {
            $object_id = $func . '_' . $param[0];
            //已创建的对象
            if (isset($this->objects[$object_id])) {
                return $this->objects[$object_id];
            } else {
                $this->factory_key = $param[0];
                $object = require self::$app_path . '/Factory/' . $func . '.php';
                $this->objects[$object_id] = $object;
                return $object;
            }
        } else {
            throw new Exception("找不到组件构造函数[$func].");
        }
    }

    /**
     * 设置路由器
     * @param $function
     */
    function router($function)
    {
        $this->router_function = $function;
    }

    function urlRoute()
    {
        if (empty($this->hooks[self::HOOK_ROUTE])) {
            echo Gimay\Error::info('服务器无法提供服务!', "路由配置钩子没有设定!", 201);
            return false;
        }
        $uri = strstr($this->request->meta['uri'], '?', true);
        if ($uri === false) {
            $uri = $this->request->meta['uri'];
        }
        $uri = trim($uri, '/');

        $mvc = array();

        //URL Router
        foreach ($this->hooks[self::HOOK_ROUTE] as $hook) {
            if (!is_callable($hook)) {
                trigger_error("钩子函数[$hook]没有找到!");
                continue;
            }
            $mvc = $hook($uri, $this);
            //命中
            if ($mvc !== false) {
                break;
            }
        }
        return $mvc;
    }

    /*
     * 接收请求事件
     */
    function handlerServer(Gimay\Request $request)
    {
        $response = new Gimay\Response();
        $request->setGlobal();//将接收的数据设置到全局变量,$_GET,$_POST,$_REQUEST

        //处理静态请求
        if (!empty(C('apps.do_static', true)) and $this->server->doStaticRequest($request, $response)) {
            return $response;
        }

        $php = Gimay::getInstance();

        //将对象赋值到控制器
        $php->request = $request;
        $php->response = $response;
        try {
            try {
                $php->is_runclient = false;
                ob_start();
                /*---------------------处理MVC----------------------*/
                $response->body = $php->runMVC();
                $response->body .= ob_get_contents();
                ob_end_clean();
                if (empty($response->head)) {
                    $this->http->header('Cache-Control', 'no-cache, must-revalidate');
                    $this->http->header('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT');
                    $this->http->header('Content-Type', 'application/json');
                }
            } catch (Gimay\ResponseException $e) {
                if ($request->finish != 1) {
                    $this->server->httpError(500, $response, $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            $this->server->httpError(500, $response, $e->getMessage() . "<hr />" . nl2br($e->getTraceAsString()));
        }

        //重定向
        if (isset($response->head['Location']) and ($response->http_status < 300 or $response->http_status > 399)) {
            $response->setHttpStatus(301);
        }
        if ($this->is_runclient) {
            return false;
        } else {
            return $response;
        }
    }

    /**
     * 运行MVC处理模型
     * @param $url_processor
     */
    function runMVC()
    {
        $mvc = call_user_func($this->router_function);
        if ($mvc === false) {
            $this->http->status(404);
            return Gimay\Error::info('读取路由配置失败!', "路由配置函数没有设置!", 201);
        }

        //check controller name
        if (!preg_match('/^[a-z0-9_]+$/i', $mvc['controller'])) {
            return Gimay\Error::info('接口不能为空!', "接口[{$mvc['controller']}]命名不正确!");
        }
        //check view name
        if (!preg_match('/^[a-z0-9_]+$/i', $mvc['view'])) {
            return Gimay\Error::info('接口不能为空!', "方法[{$mvc['view']}]命名不正确!");
        }
        //check app name
        if (isset($mvc['app']) and !preg_match('/^[a-z0-9_]+$/i', $mvc['app'])) {
            return Gimay\Error::info('接口不能为空!', "应用[{$mvc['app']}]命名不正确!");
        }
        $this->env['mvc'] = $mvc;

        $is_gateway = C('server.is_gateway', true);
        /* 网关服务负责OAuth2认证和sign验证,同时负责接口映射 */
        if ($is_gateway) {
            /* 非oauth请求都需要进行sign验证 */
            if (strtolower($mvc['controller']) != 'sns' || strtolower($mvc['view']) != 'oauth') {
                $CheckResult = $this->auth->checkSign($this);
                //签名不通过
                if (!empty($CheckResult)) {
                    return Gimay\Error::info($CheckResult['message'], json_encode($CheckResult['info']), 303);
                }
            }
        }

        /* 频率限制 */
        if (C('server.is_limit', true)) {
            /* appid作为频率限制key */
            $appid = getArray($this->request->get, 'appid');
            if (empty($appid)) {
                $key = $this->request->get['access_token'];
                $info = $this->auth->getTokenInfo($key);
                $appid = $info['appid'];
            }

            if ($this->limit->exceed($appid)) {
                return Gimay\Error::info('接口调用太频繁,请稍后再试!', json_encode($this->env), 306);
            }
            $this->limit->addCount($appid);
        }

        /* 非modules里的应用都需要查询服务列表,进行反射 */
        if ($this->srad->check($mvc['controller'])) {
            if ($is_gateway) {
                /* 发现服务 */
                $Module_Server = $this->Srad->find(strtolower($mvc['controller']));
                if (!empty($Module_Server)) {
                    $this->is_runclient = true;
                    /* 反射,调用应用模块服务器接口,优先选择外部IP,外部IP不存在选择内部IP */

                    /* 直接执行重连接会有问题,直接销毁 */
                    if (isset($this->client)) {
                        unset($this->client);
                    }
                    $path = '';
                    /* 筛选掉timestamp,access_token,sign参数 */
                    foreach ($this->request->get as $key => $value) {
                        if (in_array(strtolower($key), array('timestamp', 'access_token', 'sign'))) {
                            continue;
                        }
                        if (empty($path)) {
                            $path = '?';
                        } else {
                            $path .= '&';
                        }
                        $path .= $key . '=' . $value;
                    }
                    $path = $this->request->meta['path'] . $path;
                    $uri = array(
                        'path' => $path,
                        'host' => (!empty($Module_Server['oip']) ? $Module_Server['oip'] : $Module_Server['ip']),
                        'port' => (int)$Module_Server['port']
                    );
                    $Method = $this->request->meta['method'];
                    $this->client = new Gimay\Client\HttpClient($this, $uri);
                    if (strtolower($Method) == 'get') {
                        $this->client->get();
                        return false;
                    } else {
                        $IPost = $this->request->post;
                        $this->client->post($IPost);
                        return false;
                    }
                } else {
                    return Gimay\Error::info('接口不存在!', strtolower($mvc['controller']) . '应用模块服务找不到,或已过期!');
                }
            } else {
                return Gimay\Error::info('接口不存在!', strtolower($mvc['controller']) . '应用模块服务找不到,或已过期!');
            }
        }

        /* 使用命名空间，文件名必须大写 */
        $controller_class = '\\App\\Controller\\' . ucwords($mvc['controller']);
        if (self::$controller_path) {
            $controller_path = self::$controller_path . '/' . ucwords($mvc['controller']) . '.php';
        } else {
            $controller_path = self::$app_path . '/Controllers/' . ucwords($mvc['controller']) . '.php';
        }

        if (class_exists($controller_class, false)) {
            goto do_action;
        } else {
            if (is_file($controller_path)) {
                require_once $controller_path;
                goto do_action;
            }
        }

        //file not found
        $this->http->status(404);
        return Gimay\Error::info('接口不存在!', "{$mvc['controller']}[{$controller_path}]不存在![{$controller_class}]");

        do_action:

        //热更新,服务器模式下，尝试重载入代码
        if (defined('GIMAY_SERVER')) {
            $this->reloadController($mvc, $controller_path);
        }
        $controller = new $controller_class($this);
        if (!method_exists($controller, $mvc['view'])) {
            $this->http->status(404);
            return Gimay\Error::info('接口不存在!', "{$mvc['controller']}->{$mvc['view']}找不到!");
        }

        $param = empty($mvc['param']) ? null : $mvc['param'];
        $method = $mvc['view'];

        //doAction
        $return = $controller->$method($param);
        //保存Session
        if (defined('GIMAY_SERVER') and $this->session->open and $this->session->readonly === false) {
            $this->session->save();
        }
        //响应请求,返回json和jsonp
        if (!empty($controller->jsonp)) {
            $this->http->header('Content-type', 'application/x-javascript');
            return $controller->jsonp . "(" . json_encode($return) . ");";
        } else {
            $this->http->header('Cache-Control', 'no-cache, must-revalidate');
            $this->http->header('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT');
            $this->http->header('Content-Type', 'application/json');
            $return = json_encode($return);
        }
        if (defined('GIMAY_SERVER')) {
            return $return;
        } else {
            echo $return;
        }
    }

    /*
     * 系统热更新,需要在配置文件Config.ini里加入auto_reload=1
     */
    function reloadController($mvc, $controller_file)
    {
        if (extension_loaded('runkit') and C('apps.auto_reload', true)) {
            clearstatcache();
            $fstat = stat($controller_file);
            //修改时间大于加载时的时间
            if (isset($this->env['controllers'][$mvc['controller']]) && $fstat['mtime'] > $this->env['controllers'][$mvc['controller']]['time']) {
                runkit_import($controller_file, RUNKIT_IMPORT_CLASS_METHODS | RUNKIT_IMPORT_OVERRIDE);
                $this->env['controllers'][$mvc['controller']]['time'] = time();
            } else {
                $this->env['controllers'][$mvc['controller']]['time'] = time();
            }
        }
    }
}

/*
 * 获取路由配置
 */
function urlrouter_rewrite(&$uri)
{
    $rewrite = C('ReWrite');

    if (empty($rewrite) or !is_array($rewrite)) {
        return false;
    }
    $match = array();
    $uri_for_regx = '/' . $uri;
    foreach ($rewrite as $rule) {
        if (preg_match('#' . $rule['regx'] . '#i', $uri_for_regx, $match)) {
            if (isset($rule['get'])) {
                $p = explode(',', $rule['get']);
                foreach ($p as $k => $v) {
                    if (isset($match[$k + 1])) {
                        $_GET[$v] = $match[$k + 1];
                    }
                }
            }
            return $rule['mvc'];
        }
    }
    return false;
}

/*
 * 获取MVC控制器和视图
*/
function urlrouter_mvc(&$uri, \Gimay $gimay)
{
    $array = Gimay::$default_controller;
    if (!empty($gimay->request->get["c"])) {
        $array['controller'] = $gimay->request->get["c"];
    }
    if (!empty($gimay->request->get["v"])) {
        $array['view'] = $gimay->request->get["v"];
    }
    $request = explode('/', $uri, 3);
    if (count($request) < 2) {
        return $array;
    }
    $array['controller'] = $request[0];
    $array['view'] = $request[1];
    if (isset($request[2])) {
        $request[2] = trim($request[2], '/');
        $_id = str_replace('.html', '', $request[2]);
        if (is_numeric($_id)) {
            $_GET['id'] = $_id;
        } else {
            Gimay\Tool::$url_key_join = '-';
            Gimay\Tool::$url_param_join = '-';
            Gimay\Tool::$url_add_end = '.html';
            Gimay\Tool::$url_prefix = WEBROOT . "/{$request[0]}/$request[1]/";
            Gimay\Tool::url_parse_into($request[2], $_GET);
        }
        $_REQUEST = array_merge($_REQUEST, $_GET);
    }
    return $array;
}
