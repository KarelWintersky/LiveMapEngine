<?php

/**
 * Class LMEConfig
 */
class LMEConfig {
    private static $instance_phpauth;
    private static $instance_db;
    private static $config_main;
    private static $config_phpauth;

    public static function set_mainconfig(\INI_Config $config) {
        self::$config_main = $config;
    }

    public static function get_mainconfig():\INI_Config {
        return self::$config_main;
    }

    public static function set_authconfig(\PHPAuth\Config $config) {
        self::$config_phpauth = $config;
    }

    public static function get_authconfig(): \PHPAuth\Config {
        return self::$config_phpauth;
    }

    public static function set_auth(\PHPAuth\Auth $auth) {
        self::$instance_phpauth = $auth;
    }

    public static function get_auth(): \PHPAuth\Auth {
        return self::$instance_phpauth;
    }

    public static function set_dbi(\DBConnectionLite $instance_db) {
        self::$instance_db = $instance_db;
    }

    public static function get_dbi():\DBConnectionLite {
        return self::$instance_db;
    }
}
