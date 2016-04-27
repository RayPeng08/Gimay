<?php
global $php;
if (empty(C('Srad.'.$php->factory_key)))
{
    throw new Gimay\Exception\Factory("配置Srad->{$php->factory_key}找不到.");
}
return new Gimay\Srad(C('Srad.'.$php->factory_key));
