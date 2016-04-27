<?php
namespace Gimay\Client;

class HttpClient
{
    const EOF = "\r\n";
    const PORT = 80;

    protected $timeout = 30;

    public $gimay;
    public $url;
    public $uri;
    public $reqHeader;

    /**
     * @var \swoole_client
     */
    protected $cli;

    protected $buffer = '';
    protected $nparse = 0;
    protected $isError = false;
    protected $isFinish = false;
    protected $status = array();
    protected $respHeader = array();
    protected $body = '';
    protected $trunk_length = 0;
    protected $userAgent = 'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1847.116 Safari/537.36';
    protected $onReadyCallback;
    protected $post_data;
    protected $method = 'GET';

    /* 报头解析 */
    function parseHeader($data)
    {
        $parts = explode("\r\n\r\n", $data, 2);

        // parts[0] = HTTP头;
        // parts[1] = HTTP主体，GET请求没有body
        $headerLines = explode("\r\n", $parts[0]);

        // HTTP协议头,方法，路径，协议[RFC-2616 5.1]
        list($status['method'], $status['uri'], $status['protocol']) = explode(' ', $headerLines[0], 3);

        //错误的HTTP请求
        if (empty($status['method']) or empty($status['uri']) or empty($status['protocol'])) {
            return false;
        }
        unset($headerLines[0]);
        //解析Header
        $this->respHeader = \Gimay\Http\Parser::parseHeaderLine($headerLines);
        $this->status = $status;
        if (isset($parts[1])) {
            $this->buffer = $parts[1];
        }
        return true;
    }

    /* 报文解析 */
    function parseBody()
    {
        //解析trunk
        if (isset($this->respHeader['Transfer-Encoding']) and $this->respHeader['Transfer-Encoding'] == 'chunked') {
            while (1) {
                if ($this->trunk_length == 0) {
                    $_len = strstr($this->buffer, "\r\n", true);
                    if ($_len === false) {
                        $data = \Gimay\Error::info('数据量异常!', __LINE__ . ":Trunk: 长度异常, $_len", 201);
                        return $this->onResponse($data);
                    }
                    $length = hexdec($_len);
                    if ($length == 0) {
                        $this->isFinish = true;
                        return true;
                    }
                    $this->trunk_length = $length;
                    $this->buffer = substr($this->buffer, strlen($_len) + 2);
                } else {
                    //数据量不足，需要等待数据
                    if (strlen($this->buffer) < $this->trunk_length) {
                        return false;
                    }
                    $this->body .= substr($this->buffer, 0, $this->trunk_length);
                    $this->buffer = substr($this->buffer, $this->trunk_length + 2);
                    $this->trunk_length = 0;
                }
            }
            return false;
        } //普通的Content-Length约定
        else {
            if (strlen($this->buffer) < $this->respHeader['Content-Length']) {
                return false;
            } else {
                $this->body = $this->buffer;
                $this->isFinish = true;
                return true;
            }
        }
    }

    /* 解压 */
    static function gz_decode($data, $type = 'gzip')
    {
        if ($type == 'gzip') {
            return gzdecode($data);
        } elseif ($type == 'deflate') {
            return gzinflate($data);
        } elseif ($type == 'compress') {
            return gzinflate(substr($data, 2, -4));
        } else {
            return $data;
        }
    }

    /* 设置cookie */
    function setCookie()
    {

    }

    /* 使用浏览器限定 */
    function setUserAgent($userAgent)
    {
        $this->userAgent = $userAgent;
    }

    /* 设置报头 */
    function setHeader($k, $v)
    {
        $this->reqHeader[$k] = $v;
    }

    /* 开始连接 */
    function onConnect(\swoole_client $cli)
    {
        $header = $this->method . ' ' . $this->uri['path'] . ' HTTP/1.1' . self::EOF;
        $header .= 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8' . self::EOF;
        $header .= 'Accept-Encoding: gzip,deflate' . self::EOF;
        $header .= 'Accept-Language: zh-CN,zh;q=0.8,en;q=0.6,zh-TW;q=0.4,ja;q=0.2' . self::EOF;
        $header .= 'Host: ' . $this->uri['host'] . self::EOF;
        $header .= $this->userAgent . self::EOF;

        if (!empty($this->reqHeader)) {
            foreach ($this->reqHeader as $k => $v) {
                $header .= $k . ': ' . $v . self::EOF;
            }
        }

        $body = '';
        if ($this->post_data) {
            $header .= 'Content-Type: application/x-www-form-urlencoded' . self::EOF;
            $header .= 'Content-Length: ' . strlen($this->post_data) . self::EOF;
            $body = $this->post_data;
        }
        $cli->send($header . self::EOF . $body);
    }

    /* 回调函数 */
    function  onReady($func)
    {
        if (is_callable($func)) {
            $this->onReadyCallback = $func;
        } else {
            $data = \Gimay\Error::info('回调函数不存在!', __CLASS__ . ": 函数无法调用.", 201);
            return $this->onResponse($data);
        }
    }

    /* 回复 */
    function onResponse($data)
    {
        $this->gimay->response->body = $data;
        $this->gimay->server->response($this->gimay->request, $this->gimay->response);
        return true;
    }

    /* 接收事件 */
    function onReceive($cli, $data)
    {
        $this->buffer .= $data;
        if ($this->trunk_length > 0 and strlen($this->buffer) < $this->trunk_length) {
            return;
        }
        if (empty($this->respHeader)) {
            $ret = $this->parseHeader($this->buffer);
            if ($ret === false) {
                return;
            } else {
                if (strlen($this->buffer) > 0) {
                    goto parse_body;
                }
            }
        } else {
            parse_body:
            if ($this->parseBody() === true and $this->isFinish) {
                $compress_type = empty($this->respHeader['Content-Encoding']) ? '' : $this->respHeader['Content-Encoding'];
                $this->body = self::gz_decode($this->body, $compress_type);
                //call_user_func($this->onReadyCallback, $this->gimay, $this, $this->body, $this->respHeader);
                $this->close();
                $this->onResponse($this->body);
            }
        }
    }

    function onError($cli)
    {
        $data = \Gimay\Error::info('应用服务器异常!', json_encode($this->uri).'|'.socket_strerror($cli->errCode), 201);
        return $this->onResponse($data);
    }

    function onClose($cli)
    {
        //echo "Server close\n";
    }

    function execute()
    {
        /*if (empty($this->onReadyCallback)) {
            $data = \Gimay\Error::info('应用服务调用失败!', '应用服务回调函数没有设置!', 201);
            return $this->onResponse($data);
        }*/

        $cli = new \swoole_client(SWOOLE_TCP, SWOOLE_SOCK_ASYNC);
        $this->cli = $cli;
        $cli->on('connect', array($this, 'onConnect'));
        $cli->on('error', array($this, 'onError'));
        $cli->on('Receive', array($this, 'onReceive'));
        $cli->on('close', array($this, 'onClose'));
        $cli->connect($this->uri['host'], $this->uri['port'], $this->timeout, 1);
    }

    function get()
    {
        $this->execute();
    }

    function __construct(\Gimay $gimay, $url)
    {
        $this->gimay = $gimay;
        if (is_array($url)) {
            $this->uri = $url;
        } else {
            $this->url = $url;
            $this->uri = parse_url($this->url);
        }
        if (empty($this->uri['port'])) {
            $this->uri['port'] = self::PORT;
        }
    }

    function post(array $data)
    {
        $this->post_data = http_build_query($data);
        $this->method = 'POST';
        $this->execute();
    }

    function close()
    {
        $this->cli->close();
    }
}
