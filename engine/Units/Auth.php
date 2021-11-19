<?php

namespace Livemap\Units;

/**
 * User: Karel Wintersky <karel.wintersky@gmail.com>
 *
 * Class Auth over PHPAuth
 */

use PHPAuth\Config as PHPAuthConfig;
use PHPAuth\Auth as PHPAuth;

/**
 * Class Auth
 * Static wrapper over PHPAuth
 *
 * @package Arris
 */
class Auth
{
    const GLUE = '/';

    private static $_instance;

    private static $_config;

    private static $_pdo;

    public static $_current_user = null;

    /* ================================================================================== */

    /**
     *
     * @return PHPAuth
     */
    public static function getInstance()
    {
        if (!self::$_instance) {
            new self();
        }

        return self::$_instance;
    }

    /**
     *
     * @return PHPAuthConfig
     */
    public static function getConfig()
    {
        return self::$_config;
    }

    /**
     * @param $dbh
     */
    public static function init($dbh)
    {
        self::$_pdo = $dbh;
        new self();
    }

    public function __construct()
    {
        $dbh = self::$_pdo;
        self::$_config = new PHPAuthConfig($dbh);
        self::$_instance = new PHPAuth($dbh, self::$_config);
    }

    // ! helper
    public static function getCurrentUser() {
        // return true;
        return self::$_instance->getCurrentUser();
    }

    /**
     * Static call for dynamic method
     *
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws \Exception
     */
    public static function __callStatic($name, $arguments)
    {
        if (method_exists(self::getInstance(), $name)) {
            return (self::getInstance())->{$name}(...$arguments);
        }
    
        throw new \Exception( "Static method {$name} not exists in class " . get_class(self::getInstance() ) );
    }

    /**
     * @param $setting
     * @param null $default_value
     * @return array|mixed|null
     */
    public static function get($setting, $default_value = null)
    {
        if ($setting === '') {
            return $default_value;
        }

        if (!is_array($setting)) {
            $setting = explode(self::GLUE, $setting);
        }

        $ref = &self::$_config->config;

        foreach ((array) $setting as $parent) {
            if (is_array($ref) && array_key_exists($parent, $ref)) {
                $ref = &$ref[$parent];
            } else {
                return $default_value;
            }
        }
        return $ref;

    }

    public static function unsetcookie($cookie_name)
    {
        unset($_COOKIE[$cookie_name]);
        setcookie($cookie_name, null, -1, '/');
    }

}