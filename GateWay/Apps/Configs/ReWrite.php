<?php
/**
 * Created by PhpStorm.
 * User: 彭泽龙
 * Date: 2016-4-13
 * Time: 16:23
 * 路由配置,地址重写
 */
$array[] = array(
    /**
     * URL要匹配的正则表达式，这里字母是不区分大小写的
     */
    'regx' => '^/([a-z]+)/(\d+)\.html$',
    /**
     * 对应的控制器和视图
     */
    'mvc'  => array('controller' => 'Sns', 'view' => 'index'),
    /**
     * 将regx中的正则子表达式的值填充到$_GET参数中
     * 如/hello/134.html，那么就是 $_GET['app'] = hello, $_GET['id'] = 134
     */
    'get'  => 'c,v',
);
return $array;