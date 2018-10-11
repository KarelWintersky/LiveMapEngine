<?php

namespace Arris;

/**
 * User: Karel Wintersky <karel.wintersky@gmail.com>
 *
 * Class Auth
 *
 * Date: 20.09.2018, time: 12:55
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
    private static $_instance;

    private static $_config;

    private static $_pdo;

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
        } else {
            throw new \Exception( "Static method {$name} not exists in class " . get_class(self::getInstance() ) );
        }
    }


}