<?php
/**
 * Created by PhpStorm.
 * User: 彭泽龙
 * Date: 2016-4-13
 * Time: 16:19
 * 缓存配置文件
 */
$cache['session'] = array(
    'type' => 'FileCache',
    'cache_dir' => WEB_PATH.'/Cache/FileCache/',
);
$cache['master'] = array(
    'type' => 'FileCache',
    'cache_dir' => WEB_PATH.'/Cache/FileCache/',
);
return $cache;