<?php
global $php;
$config = C('Event.' . $php->factory_key);
if (empty($config) or empty($config['type'])) {
    throw new Exception("配置Event->[$php->factory_key]找不到.");
}
return new Gimay\Event($config);