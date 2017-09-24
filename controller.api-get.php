<?php
/**
 * User: Arris
 * Date: 24.09.2017, time: 16:07
 */
define('__ROOT__', __DIR__);

require_once (__ROOT__ . '/engine/__required.php');

$content = '';

switch ($_GET['source']) {
    case 'jslayout' : {
        require_once (__ROOT__ . '/engine/units/unit.JSLayoutGenerator.php');

        $map_alias = $_GET['map'] ?? NULL;
        $map_source = $_GET['datasrc'] ?? 'file';

        $js = new JSLayoutGenerator( $map_alias, $map_source );
        $js->run();
        $content = $js->content();

        break;
    }

} // end switch
echo $content;