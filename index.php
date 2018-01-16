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

// is logged
$auth = LMEConfig::get_auth();
$is_logged = $auth->isLogged();

if ($is_logged) {
    $userinfo = $auth->getCurrentSessionInfo();
    $main_template->set('is_logged', $is_logged);
    $main_template->set('is_logged_user', $userinfo['email']);
    $main_template->set('is_logged_user_ip', $userinfo['ip']);
}

// maps
$all_maps = new MapsListRender('');
$maps_list = $all_maps->run();

$main_template->set('maps_list', $maps_list);

// finish
$content = $main_template->render();
echo $content;