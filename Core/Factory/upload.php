<?php
global $php;
if (empty(C('Upload.'.$php->factory_key)))
{
    throw new Gimay\Exception\Factory("配置Upload->{$php->factory_key}找不到.");
}
return new Gimay\Upload(C('Upload.'.$php->factory_key));
