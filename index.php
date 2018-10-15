<?php
/**
 * User: Karel Wintersky <karel.wintersky@gmail.com>
 * Date: 24.09.2017, time: 14:52
 */
ini_set('pcre.backtrack_limit', 2*1024*1024); // 2 Mб
ini_set('pcre.recursion_limit', 2*1024*1024);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/engine/routing.helpers.php';
require_once __DIR__ . '/engine/routing.rules.php';
require_once __DIR__ . '/engine/websun.php';

$LME_ROOT = getenv( 'LME_INSTALL_PATH ');
if ($LME_ROOT === false) $LME_ROOT = __DIR__;
define('__ROOT__', $LME_ROOT);
define('PATH_STORAGE',  __ROOT__ . '/storage/');

use Pecee\SimpleRouter\SimpleRouter;
use Arris\Config;
use Arris\DB;
use Arris\Template;
use Arris\Auth;
use Arris\AppLogger;

//@todo HINT Получение данных из глобального конфига: Config::get('auth/cookies/new_registred_username');
//@todo HINT Получение данных из конфига PHPAuth делается так: Auth::get(setting)

try {
    // load Config
    Config::init([
        'config/config.php'
    ]);

    // init DB
    DB::init(NULL, Config::get('database'));

    // init Auth wrapper
    Auth::init( DB::getConnection());

    // init monolog
    AppLogger::init( Config::get('monolog' ));


    // start routing
    SimpleRouter::start();


} catch (PDOException | Exception $e) {
    die($e->getMessage());
}

