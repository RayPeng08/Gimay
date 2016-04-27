<?php
global $php;
if (empty(C('DB.' . $php->factory_key))) {
    throw new Gimay\Exception\Factory("配置DB->{$php->factory_key}找不到.");
}
$db = new Gimay\Database(C('DB.' . $php->factory_key));
$db->connect();
return $db;
