<?php
global $php;
if (empty(C('Limit.'.$php->factory_key)))
{
    throw new Gimay\Exception\Factory("配置Limit->{$php->factory_key}找不到.");
}
return new Gimay\Limit(C('Limit.'.$php->factory_key));
