<?php

class LMEAuth
{
    private static $config = null;

    private static $instance = null;

    public static $is_logged = false;

    public static $userinfo = null;

    public static function init(\PHPAuth\Auth $auth) {
        self::$instance = $auth;

        if ($auth) {
            self::$is_logged = $auth->isLogged();

            self::$userinfo = $auth->getCurrentSessionInfo();
        }
    }

    public static function auth(): \PHPAuth\Auth {
        return self::$instance;
    }






    public static function set_config(\PHPAuth\Config $config) {
        self::$config = $config;
    }

    public static function get_config(): \PHPAuth\Config {
        return self::$config;
    }


}
