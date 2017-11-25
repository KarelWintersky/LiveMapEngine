<?php
/**
 * User: Arris
 * Date: 24.09.2017, time: 16:03
 */

define('__ROOT__', __DIR__);

require_once (__ROOT__ . '/engine/__required.php');
require_once (__ROOT__ . '/engine/units/unit.MapRender.php');

$valid_view_modes = array(
    'tabled+colorbox', 'folio', 'iframe', 'wide:infobox>regionbox', 'wide:regionbox>infobox'
);

$alias_map  = $_GET['alias'] ?? NULL;
$viewmode = filter_array_for_allowed($_GET, 'viewmode', $valid_view_modes, 'folio');

$map = new MapRender( $alias_map );
$map_found = $map->run( $viewmode );
$content = $map->content();
echo $content;


