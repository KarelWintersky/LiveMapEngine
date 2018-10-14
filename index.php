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

// legacy classes
require_once (__DIR__ . '/engine/core.LMEAuth.php');


use Pecee\SimpleRouter\SimpleRouter;
use Arris\Config;
use Arris\DB;
use Arris\Template;
use Arris\Auth;

try {
    Config::init([
        'config/config.php'
    ]);
    //@todo HINT Получение данных из глобального конфига: Config::get('auth/cookies/new_registred_username');

    DB::init(NULL, Config::get('database'));

    Auth::init( DB::getConnection());
    //@todo HINT Получение данных из конфига PHPAuth делается так: Auth::get(setting)

    SimpleRouter::start();




} catch (Exception $e) {
    die($e->getMessage());
}

die;

// maps
$all_maps = new MapsListRender('');
$maps_list = $all_maps->run();

// finish
$content = $main_template->render();
echo $content;