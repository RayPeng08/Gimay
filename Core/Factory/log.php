<?php
global $php;
if (empty(C('Log.'.$php->factory_key)))
{
    throw new Gimay\Exception\Factory("配置Log->{$php->factory_key}找不到.");
}
$conf = C('Log.'.$php->factory_key);
if (empty($conf['type']))
{
    $conf['type'] = 'EchoLog';
}
$class = 'Gimay\\Log\\' . $conf['type'];
$log = new $class($conf);
if (!empty($conf['level']))
{
    $log->setLevel($conf['level']);
}
return $log;