<?php
/**
 * User: Arris
 * Date: 24.09.2017, time: 14:52
 */

define('__ROOT__', __DIR__);

require_once (__ROOT__ . '/engine/__required.php');
require_once (__ROOT__ . '/engine/units/unit.MapsListRender.php');

$main_template = new Template('index.html', '$/templates');
$main_template->set('autoactivation', LMEConfig::get_mainconfig()->get('auth/auto_activation'));

$main_template->set('is_logged', LMEAuth::$is_logged);
$main_template->set('is_logged_user', LMEAuth::$userinfo['email']);
$main_template->set('is_logged_user_ip', LMEAuth::$userinfo['ip']);

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