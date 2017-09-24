<?php
/**
 * User: Arris
 * Date: 24.09.2017, time: 16:03
 */

define('__ROOT__', __DIR__);

require_once (__ROOT__ . '/engine/__required.php');
require_once (__ROOT__ . '/engine/units/unit.MapRender.php');

$lm_engine = new LiveMapEngine( LMEConfig::get_dbi() );

// alias = mapname
// from file!

$lme_alias_map = $_GET['alias'] ?? NULL;

/*$map = new MapRender( $map_alias );
$map_found = $map->run();
if ($map_found) {
    $content = $map->content();
}*/

$regions_with_data = $lm_engine->getRegionsWithInfo( $lme_alias_map );

$regions_with_data_order_by_title = $regions_with_data;
usort($regions_with_data_order_by_title, function($value1, $value2){
    return ($value1['title'] > $value2['title']);
});

$regions_with_data_order_by_date = $regions_with_data;
usort($regions_with_data_order_by_date, function($value1, $value2){
    return ($value1['edit_date'] < $value2['edit_date']);
});

$template_data = array(
    // 'target'                =>  filter_array_for_allowed($_GET, 'target', array('iframe', 'tiddlywiki'), FALSE),

    'map_alias'             =>  $lme_alias_map,
    'map_viewport_width'    =>  filter_input(INPUT_GET, 'width', FILTER_VALIDATE_INT) ?? 800,       // get width from map settings
    'map_viewport_height'   =>  filter_input(INPUT_GET, 'height', FILTER_VALIDATE_INT) ?? 600,      // get height from map settings

    // regions
    'map_regions_with_info_jsarray'     =>  $lm_engine->convertRegionsWithInfo_to_IDs_String( $regions_with_data ),
    'map_regions_order_by_title'        =>  $regions_with_data_order_by_title,
    'map_regions_order_by_date'         =>  $regions_with_data_order_by_date,
    'map_regions_count'                 =>  count($regions_with_data)
);

$template_file = 'view.map.colorbox.html';
$html = websun_parse_template_path($template_data, $template_file, PATH_TEMPLATES);
echo $html;

