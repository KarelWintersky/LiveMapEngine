<?php
/**
 * User: Arris
 * Date: 24.09.2017, time: 16:07
 */
define('__ROOT__', __DIR__);
require_once (__ROOT__ . '/engine/__required.php');

$content = '';
$render_type = 'raw'; // == text (сырые данные без обработки)

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
        $template  = $_GET['resultType'] ?? 'html';

        $region_data = $lm_engine->getMapRegionData( $map_alias , $id_region );

        $is_logged = LMEConfig::get_auth()->isLogged();

        $can_edit = false;
        if ($is_logged) {
            $user_id = LMEConfig::get_auth()->getCurrentUID();

            $can_edit = $lm_engine->checkACL_Role($user_id, $map_alias, 'edit');
        }
        //

        $TEMPLATE_DATA = array(
            'is_present'        =>  $region_data['is_present'],

            'region_id'         =>  $id_region,
            'region_title'      =>  $region_data['title'],
            'region_text'       =>  $region_data['content'],
            'can_edit'          =>  $can_edit
        );
        $TEMPLATE_PATH = PATH_TEMPLATES . 'view.region/';

        switch ($template) {
            case 'iframe': {
                $render_type = 'text';
                $template_file = 'view.region.iframe.html';

                $content = websun_parse_template_path($TEMPLATE_DATA, $template_file, $TEMPLATE_PATH);
                break;
            }

            case 'json' : {
                $render_type = 'json';
                $template_file = 'view.region.json.html';

                $content = [
                    'content'   =>  websun_parse_template_path($TEMPLATE_DATA, $template_file, $TEMPLATE_PATH),
                    'title'     =>  ($region_data['is_present']) ? $region_data['title'] : ''
                ];
                break;
            }
            case 'fieldset': {
                $render_type = 'text';
                $template_file = 'view.region.fieldset.html';

                $content = websun_parse_template_path($TEMPLATE_DATA, $template_file, $TEMPLATE_PATH);
                break;
            }
            default     : {
                $render_type = 'text';
                $template_file = 'view.region.html.html';

                $content = websun_parse_template_path($TEMPLATE_DATA, $template_file, $TEMPLATE_PATH);

                break;
            }
        }; // switch ($template)

        break;
    }


} // end switch

if ($render_type == 'text' || $render_type == 'raw') {

    echo $content;

} elseif ($render_type === 'json') {
    echo json_encode( $content );
} else echo null;

// echo $content;