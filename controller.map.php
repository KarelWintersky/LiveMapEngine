<?php
/**
 * User: Arris
 * Date: 24.09.2017, time: 16:03
 */

define('__ROOT__', __DIR__);

require_once (__ROOT__ . '/engine/__required.php');
require_once (__ROOT__ . '/engine/units/unit.MapRender.php');

// alias = mapname
// from file!

$map_alias = $_GET['alias'] ?? NULL;

$map = new MapRender( $map_alias );
$map_found = $map->run();
if ($map_found) {
    $content = $map->content();
}

echo $content;

