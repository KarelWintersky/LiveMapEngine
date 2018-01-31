<?php

class LMEAuth
{
    private static $config = null;

    /**
     * @param \PHPAuth\Auth null
     */
    public static $instance = null;

    public static $is_logged = false;

    public static $uid      = null;

    public static $userinfo = null;

    public static function init(\PHPAuth\Auth $auth) {
        self::$instance = $auth;

        if ($auth) {
            self::$is_logged = $auth->isLogged();

            self::$userinfo = $auth->getCurrentSessionInfo();

            if (self::$userinfo) {
                self::$uid = (int)self::$userinfo['uid']; // ? == $auth->getCurrentUID();
            }
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

    public static function get(): \PHPAuth\Auth {
        return self::$instance;
    }

    public static function get_instance(): \PHPAuth\Auth {
        return self::$instance;
    }

    public static function set(\PHPAuth\Auth $instance) {
        self::$instance = $instance;
    }

    public static function set_instance(\PHPAuth\Auth $instance) {
        self::$instance = $instance;
    }


}
