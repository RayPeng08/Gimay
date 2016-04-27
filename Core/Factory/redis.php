<?php
global $php;

$config = C('Redis.' . $php->factory_key);
if (empty($config) or empty($config['host'])) {
    throw new Exception("配置Redis[$php->factory_key]找不到.");
}

if (empty($config['port'])) {
    $config['port'] = 6379;
}

if (empty($config["pconnect"])) {
    $config["pconnect"] = false;
}

if (empty($config['timeout'])) {
    $config['timeout'] = 0.5;
}

$redis = new \Gimay\Redis($config);
return $redis;