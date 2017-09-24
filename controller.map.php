<?php
/**
 * User: Arris
 * Date: 24.09.2017, time: 16:03
 */

define('__ROOT__', __DIR__);

require_once (__ROOT__ . '/engine/__required.php');
require_once (__ROOT__ . '/engine/units/unit.MapRender.php');

$lme_alias_map = $_GET['alias'] ?? NULL;

$map = new MapRender( $lme_alias_map );
$map_found = $map->run('colorbox');
$content = $map->content();
echo $content;


