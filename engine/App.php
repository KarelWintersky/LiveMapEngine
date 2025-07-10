<?php

namespace Livemap;

use Arris\AppLogger;
use Arris\Core\Dot;
use Arris\Database\Config;
use Arris\Database\Connector;
use Arris\DelightAuth\Auth\Auth;
use Arris\Helpers\Server;
use Arris\Path;
use Arris\Presenter\FlashMessages;
use Arris\Presenter\Plugins;
use Arris\Presenter\Template;
use PDO;
use Smarty;

use Kuria\Error\ErrorHandler;
use Kuria\Error\Screen\WebErrorScreen;
use Kuria\Error\Screen\WebErrorScreenEvents;

class App extends \Arris\App
{
    /**
     * @var PDO
     */
    public static PDO $pdo;

    /**
     * @var Smarty
     */
    public static Smarty $smarty;

    /**
     * @var Dot
     */
    public static Dot $config;

    /**
     * @var Auth
     */
    public static $auth;

    /**
     * @var Template;
     */
    public static $template;

    /**
     * @var FlashMessages
     */
    public static $flash;

    public static function init()
    {
        $app = App::factory();

        $_path_install = Path::create( getenv('PATH.INSTALL') );
        $_path_monolog = Path::create( getenv('PATH.LOGS') );

        config('path', [
            'install'   =>      $_path_install->toString(true),
            'logs'      =>      $_path_monolog->toString(true),
            'public'    =>      $_path_install->join('public')->toString('/'),
            'cache'             =>  $_path_install->join('cache')->toString('/'),
            'storage'           =>  getenv('PATH.STORAGE')
        ]);

        config('app', [
            'copyright'     =>  getenv('COPYRIGHT') ?? 'LiveMap Engine version 1.5+ "Algrist"',
        ]);

/*        config('domains', [
            'scheme'    =>  getenv('SCHEME'),
            'site'      =>  getenv('DOMAIN'),
            'fqdn'      =>  getenv('DOMAIN.FQDN')
        ]);*/

        /*config('limits', [
            'MAX_UPLOAD_SIZE'   =>  min(get_ini_value('post_max_size'), get_ini_value('upload_max_filesize'), Common::return_bytes(_env('MAX_UPLOAD_SIZE', '64M')))
        ]);*/
    }

    public static function initErrorHandler()
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

    /**
     * @throws \Exception
     */
    public static function initLogger()
    {
        AppLogger::init('Livemap', bin2hex(\random_bytes(8)), [
            'default_logfile_path'      => config('path.logs'),
            'default_logfile_prefix'    => date_format(date_create(), 'Y-m-d') . '__'
        ] );
    }

    public static function initTemplate()
    {
        $app = self::factory();

        config('smarty', [
            'path_template'     =>  config('path.install') . 'templates/',
            'path_cache'        =>  config('path.cache'),
            'force_compile'     =>  _env('DEBUG.SMARTY_FORCE_COMPILE', false, 'bool')
        ]);

        App::$template = new \Arris\Presenter\Template();
        App::$template
            ->setTemplateDir(config('smarty.path_template'))
            ->setCompileDir(config('smarty.path_cache'))
            ->setForceCompile(config('smarty.force_compile'))
            ->registerPlugin(Template::PLUGIN_MODIFIER, 'dd', 'dd', false)
            ->registerPlugin(Smarty::PLUGIN_MODIFIER, 'size_format', [ Plugins::class, 'size_format' ], false)
            ->registerPlugin(Smarty::PLUGIN_MODIFIER, "convertDateTime", "convertDateTime")
            ->registerPlugin(Smarty::PLUGIN_MODIFIER, "json_decode", "json_decode")
            ->registerPlugin(Smarty::PLUGIN_MODIFIER, "json_encode", "json_encode")
            ->registerClass("Arris\AppRouter", "Arris\AppRouter");

        $app->addService(Template::class, App::$template);

        App::$flash = new FlashMessages();
    }

    public static function initMobileDetect()
    {
        $MOBILE_DETECT_INSTANCE = new \Detection\MobileDetect();
        config('features', [
            'is_cli'        =>  PHP_SAPI === "cli",
            'is_mobile'     =>  PHP_SAPI !== "cli" && $MOBILE_DETECT_INSTANCE->isMobile(),
            'is_iphone'     =>  $MOBILE_DETECT_INSTANCE->is('iPhone'),
            'is_android'    =>  $MOBILE_DETECT_INSTANCE->is('Android'),
        ]);
    }

    public static function initDBConnection()
    {
        $app = self::factory();

        /**
         * Database
         */
        $db_credentials = [
            'driver'            =>  'mysql',
            'hostname'          =>  getenv('DB.HOST'),
            'database'          =>  getenv('DB.NAME'),
            'username'          =>  getenv('DB.USERNAME'),
            'password'          =>  getenv('DB.PASSWORD'),
            'port'              =>  getenv('DB.PORT'),
            'charset'           =>  'utf8',
            'charset_collate'   =>  'utf8_general_ci',
            'slow_query_threshold'  => 1
        ];
        config('db_credentials', $db_credentials);

        $db_config = new Config(logger: AppLogger::scope('mysql'));
        $db_config
            ->setHost(getenv('DB.HOST'))
            ->setPort(getenv('DB.PORT'))
            ->setUsername(getenv('DB.USERNAME'))
            ->setPassword(getenv('DB.PASSWORD'))
            ->setDatabase(getenv('DB.NAME'))
            ->setDriver(Config::DRIVER_MYSQL)
        ;

        App::$pdo = new Connector($db_config);

        $app->addService('pdo', App::$pdo);
    }

    public static function initAuth()
    {
        $app = self::factory();

        /**
         * Auth Delight
         */
        App::$auth = new Auth(App::$pdo);
        $app->addService(Auth::class, App::$auth);
        config('auth', [
            'id'            =>  App::$auth->id(),
            'is_logged_in'  =>  App::$auth->isLoggedIn(),       // флаг "залогинен"
            'username'      =>  App::$auth->getUsername(),      // пользователь
            'email'         =>  App::$auth->getEmail(),
            'ipv4'          =>  Server::getIP(),                // IPv4

            // основная роль пользователя
            // 'is_banned'     =>  App::$auth->hasRole(\Livemap\AuthRoles::BANNED),
            // 'is_viewer'     =>  App::$auth->hasRole(\Livemap\AuthRoles::VIEWER),    // просмотр
            // 'is_editor'     =>  App::$auth->hasRole(\Livemap\AuthRoles::EDITOR),      // загрузка и редактирование
            // 'is_curator'    =>  App::$auth->hasRole(\Livemap\AuthRoles::CURATOR),     // куратор: статистика
            'is_admin'      =>  App::$auth->hasRole(\Livemap\AuthRoles::ADMIN),       // админ
        ]);
    }

    public static function initRedis()
    {
    }


}