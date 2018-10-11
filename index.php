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

    DB::init(NULL, Config::get('database'));

    Auth::init( DB::getConnection());

    SimpleRouter::start();










} catch (Exception $e) {
    die($e->getMessage());
}

die;

define('__ROOT__', __DIR__);

require_once (__ROOT__ . '/engine/__required.php');
require_once (__ROOT__ . '/engine/units/unit.MapsListRender.php');

$main_template = new Template('index.html', '$/templates');
$main_template->set('autoactivation', LMEConfig::get_mainconfig()->get('auth/auto_activation'));

$main_template->set('authinfo', [
    'is_logged' =>  LMEAuth::$is_logged,
    'email'     =>  LMEAuth::$userinfo['email'],
    'ip'        =>  LMEAuth::$userinfo['ip']
]);


// maps
$all_maps = new MapsListRender('');
$maps_list = $all_maps->run();

$main_template->set('maps_list', $maps_list);

// finish
$content = $main_template->render();
echo $content;