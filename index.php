<?php
/**
 * User: Arris
 * Date: 24.09.2017, time: 14:52
 */

require_once 'vendor/autoload.php';
require_once 'engine/routing.helpers.php';

require_once 'engine/routing.rules.php';

use Pecee\SimpleRouter\SimpleRouter;


$config = [
    'adapter'   =>  'mysql',
    'hostname'  =>  'localhost',
    'username'  =>  'phpauthdemo',
    'password'  =>  'password',
    'database'  =>  'phpauthdemo',
    'charset'   =>  'utf8',
    'port'      =>  3306
];

DB::init(NULL, $config);
SimpleRouter::start();



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