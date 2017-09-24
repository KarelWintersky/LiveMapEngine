<?php
/**
 * User: Arris
 * Date: 24.09.2017, time: 14:52
 */

define('__ROOT__', __DIR__);

require_once (__ROOT__ . '/engine/__required.php');
require_once (__ROOT__ . '/engine/units/unit.MapsListRender.php');

$all_maps = new MapsListRender('');
$all_maps->run('');
$content = $all_maps->content();

echo $content;

