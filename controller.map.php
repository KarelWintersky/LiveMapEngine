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
$viewmode = 'wide:infobox>regionbox';

/**
 * @var stdClass $map_config
 */
$map_config = NULL;

//@todo: дергаем инфу из базы. Если есть в БД описание карты - ок, нет - дёргаем файл. Нет файла - ква.

try {
    $alias_map  = $_GET['alias'] ?? NULL;

    $cfl = new LMEMapConfigLoader($alias_map, 'file');
    if ($cfl->ERROR)
        throw new \Exception($cfl->ERROR_MESSAGE);

    $cfl->loadConfig();
    if ($cfl->ERROR)
        throw new \Exception($cfl->ERROR_MESSAGE);

    $map_config = $cfl->getConfig();

    if (!empty($map_config->display->viewmode))
        $viewmode = $map_config->display->viewmode;

    // перекрываем его из $_GET
    $viewmode = filter_array_for_allowed($_GET, 'viewmode', $valid_view_modes, $viewmode);
    $viewmode = filter_array_for_allowed($_GET, 'view',     $valid_view_modes, $viewmode);

    $map = new MapRender( $alias_map, $map_config );
    $map_found = $map->run( $viewmode );
    $content = $map->content();
    $content = preg_replace('/^\h*\v+/m', '', $content);

    echo $content;

} catch (\Exception $e) {
    die( $e->getMessage() );
}





