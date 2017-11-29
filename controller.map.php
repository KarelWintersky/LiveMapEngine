<?php
/**
 * User: Arris
 * Date: 24.09.2017, time: 16:03
 */

define('__ROOT__', __DIR__);

require_once (__ROOT__ . '/engine/__required.php');
require_once (__ROOT__ . '/engine/units/unit.MapRender.php');

$valid_view_modes = array(
    'colorbox', 'tabled:colorbox', 'folio', 'iframe', 'iframe:colorbox', 'wide:infobox>regionbox', 'wide:regionbox>infobox'
);
// дефолтный режим просмотра
$viewmode = 'wide:infobox>regionbox'; // default view mode

$alias_map  = $_GET['alias'] ?? NULL;
if (!$alias_map) die('404');

// загружаем "скин" из json-файла (или БД) карты
$filename = PATH_STORAGE . $alias_map . '/index.json';
if (!is_file($filename)) {
    die('Incorrect path: ' . PATH_STORAGE . $alias_map);
}

$json = json_decode( file_get_contents( $filename ) );

if (!empty($json->viewport->viewmode))
    $viewmode = $json->viewport->viewmode;

// перекрываем его из $_GET
$viewmode = filter_array_for_allowed($_GET, 'viewmode', $valid_view_modes, $viewmode);
$viewmode = filter_array_for_allowed($_GET, 'view',     $valid_view_modes, $viewmode);

$map = new MapRender( $alias_map, $json );
$map_found = $map->run( $viewmode );
$content = $map->content();
echo $content;


