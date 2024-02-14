<?php

namespace Livemap;

use AJUR\Template\FlashMessages;
use AJUR\Template\Template;
use AJUR\Template\TemplatePlugins;
use Arris\AppLogger;
use Arris\Core\Dot;
use Arris\Database\DBWrapper;
use Arris\DelightAuth\Auth\Auth;
use Arris\Helpers\Server;
use Arris\Path;
use Smarty;

use Kuria\Error\ErrorHandler;
use Kuria\Error\Screen\WebErrorScreen;
use Kuria\Error\Screen\WebErrorScreenEvents;

class App extends \Arris\App
{
    /**
     * @var DBWrapper
     */
    public static DBWrapper $pdo;

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
            'copyright'     =>  'LiveMap Engine version 1.5+ "Algrist"',
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
        $errorHandler = new ErrorHandler();
        $errorHandler->setDebug(true);
        error_reporting(E_ALL & ~E_NOTICE);
        $errorHandler->register();

        // добавлено правильное сообщение об ошибке для прода
        // смотри https://github.com/kuria/error/issues/2
        if (getenv('IS.PRODUCTION') == 1) {
            $errorScreen = $errorHandler->getErrorScreen();
            if (!$errorHandler->isDebugEnabled() && $errorScreen instanceof WebErrorScreen) {
                $errorScreen->on(WebErrorScreenEvents::RENDER, static function ($event) {
                    $event['heading'] = 'MediaBox';
                    $event['text'] = 'У нас что-то сломалось. Мы уже чиним.';
                });
            }
        }

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
        App::$smarty = new Smarty();
        App::$smarty->setTemplateDir( config('smarty.path_template'));
        App::$smarty->setCompileDir( config('smarty.path_cache'));
        App::$smarty->setForceCompile(config('smarty.force_compile'));
        App::$smarty->registerPlugin(Smarty::PLUGIN_MODIFIER, 'dd', 'dd', false);
        App::$smarty->registerPlugin(Smarty::PLUGIN_MODIFIER, 'size_format', [ TemplatePlugins::class, 'size_format' ], false);
        // App::$smarty->registerPlugin(Smarty::PLUGIN_MODIFIER, "convertDateTime", [ \AjurMedia\MediaBox\Common::class, "convertDateTime" ]);

        $app->addService(Smarty::class, App::$smarty);

        // ******
        // Smarty
        // ******

        // ********
        // Template
        // ********
        App::$template = new Template(App::$smarty, $_REQUEST, [], AppLogger::scope('smarty')); // global template

        $app->addService(Template::class, App::$template);

        // **********
        // Slim flash
        // **********
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

        App::$pdo = new DBWrapper(config('db_credentials'), [ 'slow_query_threshold' => 100 ], AppLogger::scope('mysql') );
        $app->addService('pdo', App::$pdo);
    }

    public static function initAuth()
    {
        $app = self::factory();

        /**
         * Auth Delight
         */
        App::$auth = new Auth(new \PDO(
            sprintf(
                "mysql:dbname=%s;host=%s;charset=utf8mb4",
                config('db_credentials.database'),
                config('db_credentials.hostname')
            ),
            config('db_credentials.username'),
            config('db_credentials.password')
        ));
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


}