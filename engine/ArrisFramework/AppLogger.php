<?php
/**
 * User: Arris
 *
 * Class AppLogger
 * Namespace: Arris
 *
 * Date: 15.10.2018, time: 5:33
 */

namespace Arris;

use Monolog\Handler\FilterHandler;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use KarelWintersky\Monolog;

interface AppLoggerInterface {
    public static function init($config);

    public static function setConfig($config);

    public static function getInstance();

    public static function debug(...$args);

    public static function info(...$args);

    public static function notice(...$args);

    public static function warning(...$args);

    public static function error(...$args);

    public static function critical(...$args);

    public static function alert(...$args);

    public static function emergency(...$args);
}

class AppLogger
{
    const VERSION = "1.1/ArrisFramework";

    /**
     * @var \Monolog\Logger $_log
     */
    private static $_log;

    /**
     * @var array
     */
    private static $_config;

    /** ==== */
    public static function checkInstance() {
        return self::$_log;
    }

    /** ==== */

    public static function getInstance() {
        if (!self::$_log) {
            new self();
        }

        return self::$_log;
    }

    public static function init($config) {
        self::setConfig($config);
        new self();
    }

    public static function setConfig($config) {
        self::$_config = $config;
    }

    public function __construct()
    {
        $config = self::$_config;
        $channel = $config['channel'];

        self::$_log = new Logger($channel);

        switch ($config['handler']) {

            case 'file': {
                $path = str_replace('$/', $_SERVER['DOCUMENT_ROOT'], $config['filepath']);
                $name = $channel . '.log';

                self::$_log->pushHandler(new StreamHandler($path . $name, Logger::WARNING));

                break;
            }
            case 'mysql': {
                $log_handler = new Monolog\KWPDOHandler( DB::getConnection(), $channel, [], [], Logger::INFO);
                self::$_log->pushHandler($log_handler);

                break;
            }
        }
    }

    /**
     * DEBUG (100): Detailed debug information.
     *
     * @param ...$args
     * @return bool
     */
    public static function debug(...$args)
    {
        return self::getInstance()->debug(...$args);
    }

    /**
     * INFO (200): Interesting events. Examples: User logs in, SQL logs.
     *
     * @param ...$args
     * @return bool
     */
    public static function info(...$args)
    {
        return self::getInstance()->info(...$args);
    }

    /**
     * NOTICE (250): Normal but significant events.
     *
     * @param ...$args
     * @return bool
     */
    public static function notice(...$args)
    {
        return self::getInstance()->notice(...$args);
    }

    /**
     * WARNING (300): Exceptional occurrences that are not errors. Examples: Use of deprecated APIs, poor use of an API,
     * undesirable things that are not necessarily wrong.
     *
     * @param ...$args
     * @return bool
     */
    public static function warning(...$args)
    {
        // return self::getInstance()->warning(...$args);
        return self::$_log->warning(...$args);
    }

    /**
     * ERROR (400): Runtime errors that do not require immediate action but should typically be logged and monitored.
     *
     * @param ...$args
     * @return bool
     */
    public static function error(...$args)
    {
        return self::getInstance()->error(...$args);
    }

    /**
     * CRITICAL (500): Critical conditions. Example: Application component unavailable, unexpected exception.
     *
     * @param ...$args
     * @return bool
     */
    public static function critical(...$args)
    {
        return self::getInstance()->critical(...$args);
    }

    /**
     * ALERT (550): Action must be taken immediately. Example: Entire website down, database unavailable, etc.
     * This should trigger the SMS alerts and wake you up.
     *
     * @param ...$args
     * @return bool
     */
    public static function alert(...$args)
    {
        return self::getInstance()->alert(...$args);
    }

    /**
     * EMERGENCY (600): Emergency: system is unusable.
     *
     * @param ...$args
     * @return bool
     */
    public static function emergency(...$args)
    {
        return self::getInstance()->emergency(...$args);
    }

    /** === CALL STATIC === */

    public static function __callStatic($method, $args)
    {
        if (method_exists(self::getInstance(), $method)) {
            return self::getInstance()->$method(...$args);
        }
    }


}