<?php
/**
 * Created by PhpStorm.
 * User: 彭泽龙
 * Date: 2016-4-13
 * Time: 10:07
 * 应用服务器类
 */
namespace Gimay\Protocol;
use Gimay;
class AppServerException extends \Exception
{

}

class AppServer extends HttpServer
{
    protected $router_function;
    protected $apps_path;

    function onStart($serv)
    {
        parent::onStart($serv);
        if (empty($this->apps_path))
        {
            if (defined('APP_PATH'))
            {
                $this->apps_path = APP_PATH;
            }
            else
            {
                throw new AppServerException("APP_PATH没有定义Server无法启动!");
            }
        }
        $php = Gimay::getInstance();
        $php->addHook(Gimay::HOOK_CLEAN, function(){
            $php = Gimay::getInstance();
            //还原session
            if (!empty($php->session))
            {
                $php->session->open = false;
                $php->session->readonly = false;
            }
        });
    }

    function onRequest(Gimay\Request $request)
    {
        return Gimay::getInstance()->handlerServer($request);
    }
}