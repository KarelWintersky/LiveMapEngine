<?php

namespace App;

use Arris\App as AppCore;
use Arris\AppLogger;
use Arris\Database\Config;
use Arris\Database\Connector;
use Arris\DelightAuth\Auth\Auth;
use Arris\Entity\Path;
use Arris\Presenter\FlashMessages;
use Arris\Presenter\FlashMessagesInterface;
use Arris\Presenter\Plugins;
use Arris\Presenter\Template;
use Arris\Presenter\TemplateInterface;
use Kuria\Error\ErrorHandler;
use PDO;

class App extends AppCore
{
    public static App $app;

    public static FlashMessagesInterface $flash;

    public static TemplateInterface $template;
    /**
     * @var array|Connector|mixed|null
     */
    public static PDO $pdo;
    /**
     * @var Auth|array|mixed|null
     */
    public static Auth $auth;

    protected function getDefaultConfig():array
    {
        $path_install = Path::create(dirname(__DIR__));

        return [
            'app'   =>  [
                'copyright' =>  'LiveMap Engine version 1.0 "Aerlis"'
            ],
            'path'  =>  [
                'install'   =>  $path_install->toString(true),
                'logs'      =>  $path_install->join('logs')->toString(true),
                'public'    =>  $path_install->join('public')->toString(true),
                'cache'     =>  $path_install->join('cache')->toString(true),
                'storage'   =>  $path_install->join('storage')->toString(true),
            ],
            'database'  =>  [
                'driver'    =>  Config::DRIVER_MYSQL,
                'host'      =>  '127.0.0.1',
                'port'      =>  3306,
                'username'  =>  'root',
                'password'  =>  'password',
                'database'  =>  'livemap'
            ],
            'features'  =>  [
                'rfm_version'   =>  '9_4_0', // or 9_14_0
            ],
            'debug'     =>  [
                'smarty_force_compile'  =>  true
            ]
        ];
    }

    public static function init(array $config = []):void
    {
        $app = App::factory($config);
    }

    public static function initLogger():void
    {
        AppLogger::init('Livemap', bin2hex(\random_bytes(8)), [
            'default_logfile_path'      => App::config('path.logs'),
            'default_logfile_prefix'    => date_format(date_create(), 'Y-m-d') . '__'
        ] );
    }

    public static function initDatabase():void
    {
        $db_config = new Config(logger: AppLogger::scope('mysql'));
        $db_config
            ->setHost(App::config('database.host'))
            ->setPort(App::config('database.port'))
            ->setUsername(App::config('database.username'))
            ->setPassword(App::config('database.password'))
            ->setDatabase(App::config('database.database'))
            ->setDriver(App::config('database.driver'))
        ;
        App::$pdo = new Connector($db_config);
    }

    /**
     * @throws \SmartyException
     */
    public static function initPresenter():void
    {
        /*App::factory()->addConfig([
            'smarty'    =>  [
                'test' => 5
            ]
        ]);*/

        App::$template = new \Arris\Presenter\Template();

        App::$template
            ->setTemplateDir( App::config('path.install') . 'templates/')
            ->setCompileDir(App::config('path.cache'))
            ->setForceCompile(App::config('debug.smarty_force_compile') ?? false)
            ->registerPlugin(Template::PLUGIN_MODIFIER, 'dd', 'dd', false)
            ->registerPlugin(Template::PLUGIN_MODIFIER, 'size_format', [ Plugins::class, 'size_format' ], false)
            ->registerPlugin(Template::PLUGIN_MODIFIER, "convertDateTime", "convertDateTime")
            ->registerPlugin(Template::PLUGIN_MODIFIER, "json_decode", "json_decode")
            ->registerPlugin(Template::PLUGIN_MODIFIER, "json_encode", "json_encode")
            ->registerClass("Arris\AppRouter", "Arris\AppRouter");

        App::$flash = new FlashMessages();
    }

    public static function initMobileDetect()
    {
        $MOBILE_DETECT_INSTANCE = new \Detection\MobileDetect();

        App::getInstance()->addConfig([
            'features', [
                'is_cli'        =>  PHP_SAPI === "cli",
                'is_mobile'     =>  PHP_SAPI !== "cli" && $MOBILE_DETECT_INSTANCE->isMobile(),
                'is_iphone'     =>  $MOBILE_DETECT_INSTANCE->is('iPhone'),
                'is_android'    =>  $MOBILE_DETECT_INSTANCE->is('Android'),
            ]
        ]);
    }

    public static function initAuth():void
    {
        App::$auth = new Auth(App::$pdo);

        App::getInstance()->addConfig([
            'auth' => [
                'id'            =>  App::$auth->id(),
                'is_logged_in'  =>  App::$auth->isLoggedIn(),       // флаг "залогинен"
                'username'      =>  App::$auth->getUsername(),      // пользователь
                'email'         =>  App::$auth->getEmail(),
                'ipv4'          =>  \Arris\Helpers\Server::getIP(),                // IPv4

                // основная роль пользователя
                // 'is_banned'     =>  App::$auth->hasRole(\Livemap\AuthRoles::BANNED),
                // 'is_viewer'     =>  App::$auth->hasRole(\Livemap\AuthRoles::VIEWER),    // просмотр
                // 'is_editor'     =>  App::$auth->hasRole(\Livemap\AuthRoles::EDITOR),      // загрузка и редактирование
                // 'is_curator'    =>  App::$auth->hasRole(\Livemap\AuthRoles::CURATOR),     // куратор: статистика
                'is_admin'      =>  App::$auth->hasRole(\App\AuthRoles::ADMIN),       // админ
            ]
        ]);
    }

    public static function initRedis():void
    {

    }

    public static function initErrorHandler(): void
    {
        $is_debug = !_env('IS.PRODUCTION', false, 'bool');
        $errorHandler = new ErrorHandler();
        $errorHandler->setDebug($is_debug);
        error_reporting(E_ALL & ~E_NOTICE);
        $errorHandler->register();

        // https://github.com/kuria/error

        // добавлено правильное сообщение об ошибке для прода
        // смотри https://github.com/kuria/error/issues/2
        /*if (getenv('IS.PRODUCTION') == 1) {
            $errorScreen = $errorHandler->getErrorScreen();
            if (!$errorHandler->isDebugEnabled() && $errorScreen instanceof WebErrorScreen) {
                $errorScreen->on(WebErrorScreenEvents::RENDER, static function ($event) {
                    $event['heading'] = 'Livemap';
                    $event['text'] = 'У нас что-то сломалось. Мы уже чиним.';
                });
            }
        }*/
    }


}