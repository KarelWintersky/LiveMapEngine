<?php
/**
 * User: Arris
 * Date: 24.09.2017, time: 16:07
 */
define('__ROOT__', __DIR__);
require_once (__ROOT__ . '/engine/__required.php');

$content = '';
$render_type = 'raw';

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

    case 'regiondata': {
        // require_once(__ROOT__ . '/engine/units/unit.MapRegionsManage.php');
        // $mrm = new MapRegionsManage( $map_alias );
        // $mrm->getRegionData();
        // $content = $mrm->content();

        $lm_engine = new LiveMapEngine( LMEConfig::get_dbi() );

        $map_alias = $_GET['map']   ?? NULL;
        $id_region = $_GET['id']    ?? NULL;
        $template  = $_GET['template'] ?? 'fieldset';

        $region_data = $lm_engine->getMapRegionData( $map_alias , $id_region );
        $is_logged = LMEConfig::get_auth()->isLogged();

        // проверка прав на редактирование

        $TEMPLATE_DATA = array(
            'is_present'        =>  $region_data['is_present'],

            'region_id'         =>  $id_region,
            'region_title'      =>  $region_data['title'],
            'region_text'       =>  $region_data['content'],
            'islogged'          =>  LMEConfig::get_auth()->isLogged()
        );

        switch ($template) {
            case 'html' : {
                $render_type = 'json';
                $tpl_file = 'view.region.html.html';

                $content = [
                    'content'   =>  websun_parse_template_path($TEMPLATE_DATA, $tpl_file, PATH_TEMPLATES),
                    'title'     =>  $region_data['title']
                ];

                break;
            }
            default     : {
                $render_type = 'raw';
                $tpl_file = 'view.region.fieldset.html';

                $content = websun_parse_template_path($TEMPLATE_DATA, $tpl_file, PATH_TEMPLATES);

                break;
            }
        }; // switch ($template)

        break;
    }


} // end switch

if ($render_type == 'raw') {

    echo $content;

} elseif ($render_type === 'json') {
    echo json_encode( $content );
} else echo null;

// echo $content;