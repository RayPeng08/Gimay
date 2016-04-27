<?php
global $php;
if (empty(C('Auth.'.$php->factory_key)))
{
    throw new Gimay\Exception\Factory("配置Auth->{$php->factory_key}找不到.");
}
return new Gimay\Auth(C('Auth.'.$php->factory_key));
