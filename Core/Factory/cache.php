<?php
global $php;
if ($php->factory_key == 'master' and empty(C('Cache.master'))) {
    C('Cache.master', array('type' => 'FileCache', 'cache_dir' => WEB_PATH . '/Cache/FileCache'));
}
return Gimay\Factory::getCache($php->factory_key);