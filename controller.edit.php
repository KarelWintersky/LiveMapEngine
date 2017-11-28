<?php
/**
 * User: Arris
 * Date: 25.09.2017, time: 1:58
 */
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

        if (! ($edit_map_alias && $edit_region_id)) break; // эта проверка должна делаться в роутере

        // проверяем права редактирования
        // LiveMapEngine->checkACL( $auth->getCurrentUID(),  $edit_map_alias) // должно быть editor или owner

        setcookie( LMEConfig::get_mainconfig()->get('auth/cookie_filemanager_storage_path'), $edit_map_alias, 0, '/'); // see original livemap
        setcookie( LMEConfig::get_mainconfig()->get('auth/cookie_filemanager_current_map'), $edit_map_alias, 0, '/');

        $lm_engine = new LiveMapEngine( LMEConfig::get_dbi() );

        // $map_data    = $lm_engine->getMapData( $edit_map_alias );

        $region_data = $lm_engine->getMapRegionData( $edit_map_alias, $edit_region_id );

        // читаем шаблоны из json-файла конфигурации карты (а должны из БД, таблица settings_project_edit_templates с наследованием settings_project_edit_templates)

        // и это должно быть в модели!
        $filename = PATH_STORAGE . $edit_map_alias . '/index.json';
        if (!is_file($filename)) {
            die('Incorrect path: ' . PATH_STORAGE . $this->map_alias);
        }

        $json = json_decode( file_get_contents( $filename ) );

        $edit_templates = [];
        $edit_templates_index = 1;
        $edit_templates_styles = '';

        if ($json->edit_templates) {

            foreach ($json->edit_templates->templates as $template_record) {
                $template = [
                    'title'     =>  $template_record->title ?? "#{$edit_templates_index}",
                    'desc'      =>  $template_record->description ?? "#{$edit_templates_index}",
                    'url'       =>  "/storage/{$edit_map_alias}/edit_templates/" . $template_record->url
                ];

                $edit_templates[] = $template;
                $edit_templates_index++;
            }

            $edit_templates_styles = $json->edit_templates->styles ?? "";
            if ($edit_templates_styles)
                $edit_templates_styles = "/storage/{$edit_map_alias}/edit_templates/" . $edit_templates_styles;
        }


        // конец анализа json-конфига


        $template_data = array(
            'id_region'         =>  $edit_region_id,
            'id_map'            =>  0,

            'title_map'         =>  $edit_map_alias,    // загружаем из БД

            'alias_map'         =>  $edit_map_alias,

            'html_callback'     =>  "/map/{$edit_map_alias}",

            'region_title'      =>  ($region_data['is_present'] == 1) ? htmlspecialchars($region_data['title']) : '',
            'region_text'       =>  $region_data['content'],


            'is_present'        =>  $region_data['is_present'],      // 1 - регион существует, 0 - новый регион

            'is_logged_user'    =>  $userinfo['email'],
            'is_logged_user_ip' =>  $userinfo['ip'],

            // 'edit_templates'   =>  $edit_templates,
            'edit_templates_styles' => $edit_templates_styles,

            // copyright
            'copyright'         =>  LMEConfig::get_mainconfig()->get('copyright/title'),

            // revisions
            'region_revisions'  =>  $lm_engine->getRegionRevisions( $edit_map_alias, $edit_region_id),
        );

        $template_file = 'edit.region.page.html';
        $template_path = PATH_TEMPLATES . 'edit.region/';

        $html = websun_parse_template_path($template_data, $template_file, $template_path);

        echo $html;

        break;
    }



} // switch
