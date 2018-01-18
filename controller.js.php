<?php
/**
 * User: Arris
 * Date: 16.01.2018, time: 7:44
 */
define('__ROOT__', __DIR__);
require_once (__ROOT__ . '/engine/__required.php');

$content = '';
$render_type = 'raw';

require_once (__ROOT__ . '/engine/units/unit.JSLayoutBuilder.php');

$map_alias = $_GET['map'] ?? NULL;
$map_source = $_GET['datasrc'] ?? 'file';

$js = new JSLayoutBuilder( $map_alias, $map_source );
$js->run();
$content = $js->content();

// remove empty lines from file?
// $content = implode("\n", array_filter(explode("\n", $content))); // быстрее, но оставляет часть строк
$content = preg_replace('/^\h*\v+/m', '', $content); // медленнее, но чистит все строки

echo $content;

