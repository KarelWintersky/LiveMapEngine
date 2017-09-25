<?php
/**
 * User: Arris
 * Date: 25.09.2017, time: 1:58
 */
/*echo '<pre>';
var_dump($_GET);*/

define('__ROOT__', __DIR__);
require_once (__ROOT__ . '/engine/__required.php');

$auth = LMEConfig::get_auth();

if (! $auth->isLogged() ) {
    die('Hacking attempt!');
}
$userinfo = $auth->getCurrentSessionInfo();


$edit_what = $_GET['editwhat'] ?? NULL;
switch ($edit_what) {
    case 'region': {
        $edit_map_alias = $_GET['map'] ?? NULL;
        $edit_region_id = $_GET['id'] ?? NULL;

        // проверяем права редактирования
        // LiveMapEngine->checkACL( $auth->getCurrentUID(),  $edit_map_alias) // должно быть editor или owner

        setcookie( LMEConfig::get_mainconfig()->get('auth/cookie_filemanager_storage_path'), $edit_map_alias, 0, '/'); // see original livemap
        setcookie( LMEConfig::get_mainconfig()->get('auth/cookie_filemanager_current_map'), $edit_map_alias, 0, '/');

        $lm_engine = new LiveMapEngine( LMEConfig::get_dbi() );

        // $map_data    = $lm_engine->getMapData( $edit_map_alias );
        // 'title_project'     =>  'Уинтерленд',
        // 'title_map'         =>  'Карта Норрастадена (Северного города)',

        $region_data = $lm_engine->getMapRegionData( $edit_map_alias, $edit_region_id );

        $template_data = array(
            'alias_map'         =>  $edit_map_alias,

            'id_region'         =>  $edit_region_id,

            'html_callback'     =>  "/map/{$edit_map_alias}",

            'region_title'      =>  ($region_data['is_present'] == 1) ? htmlspecialchars($region_data['title']) : '',
            'region_text'       =>  $region_data['content'],


            'is_present'        =>  $region_data['is_present'],      // 1 - регион существует, 0 - новый регион

            'is_logged_user'    =>  $userinfo['email'],
            'is_logged_user_ip' =>  $userinfo['ip'],

            // copyright
            'copyright'         =>  LMEConfig::get_mainconfig()->get('copyright/title'),

            // revisions
            'region_revisions'  =>  $lm_engine->getRegionRevisions( $edit_map_alias, $edit_region_id),
        );

        $template_file = 'edit.region.page.html';

        $html = websun_parse_template_path($template_data, $template_file, PATH_TEMPLATES);

        echo $html;

        break;
    }



} // switch
