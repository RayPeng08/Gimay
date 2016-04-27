<?php
global $php;
if (!empty(C('Session.use_session')) or defined('GIMAY_SERVER')) {
    if (empty(C('Cache.session'))) {
        $cache = $php->cache;
    } else {
        $cache = Gimay\Factory::getCache('session');
    }
    $session = new Gimay\Session($cache);
    $session->use_php_session = false;
} else {
    $session = new Gimay\Session;
}
return $session;