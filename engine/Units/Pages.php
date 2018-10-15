<?php
/**
 * User: Arris
 *
 * Class Pages
 * Namespace: LME\Units
 *
 * Date: 14.10.2018, time: 14:03
 */

namespace LME\Units;

use Arris\AppLogger;
use Arris\Auth;
use Arris\Config;
use Arris\Template;
use LivemapFramework\ACL;
use LivemapFramework\MapManager;

/**
 * Class Pages
 * @package LME\Units
 *
 * Модель, реализует методы отрисовки статических страниц
 *
 */
class Pages
{

    /**
     * Frontpage ( / )
     *
     * @return string
     */
    public function view_page_frontpage() {
        $auth = Auth::getInstance();
        $userinfo = $auth->getCurrentSessionUserInfo();

        $t = new Template('index.html', '$/templates');
        $t->set('authinfo', [
            'is_logged' =>  $auth->isLogged(),
            'email'     =>  $userinfo['email'] ?? '',
            'ip'        =>  $userinfo['ip'] ?? ''
        ]);

        {
            $maps_list = [];
            $indexfile = __ROOT__ . \Arris\Config::get('storage/maps') . '/list.json';

            if (is_readable($indexfile)) {
                $json = json_decode( file_get_contents( $indexfile ) );

                foreach ($json->maps as $i => $map) {
                    $alias = $map->alias;
                    $title = $map->title;
                    $key = str_replace('.', '~', $alias);

                    $maps_list[ $key ] = [
                        'alias' =>  $alias,
                        'title' =>  $title
                    ];
                }
            }
        }

        $t->set('maps_list', $maps_list);

        return $t->render();
    }

    public function view_page_edit_region($map_alias, $region_id) {

        $auth = Auth::getInstance();
        $userinfo = $auth->getCurrentUser();

        // проверяем права редактирования
        $current_role = ACL::getRole($userinfo['uid'], $map_alias);
        $can_edit = ACL::isValidRole($current_role, 'EDITOR');

        // Это задача middleware
        if (!$can_edit) {
            die('Not enough access rights for update info!');
        }

        setcookie( Config::get('auth/cookies/filemanager_storage_path'), $map_alias, 0, '/');
        setcookie( Config::get('auth/cookies/filemanager_current_map'), $map_alias, 0, '/');

        $map_engine = new MapManager();

        $region_data = $map_engine->getMapRegionData($map_alias, $region_id);

        // читаем шаблоны редактирования из json-файла конфигурации карты (это должно быть где-то в LivemapFramework)
        $filename = PATH_STORAGE . $map_alias . '/index.json';
        if (!is_file($filename)) {
            throw new \Exception('Incorrect path: ' . PATH_STORAGE . $map_alias);
        }

        $json = json_decode( file_get_contents( $filename ) );

        $edit_templates = [];
        $edit_templates_options = [];
        $edit_templates_index = 1;
        $edit_templates_styles = '';

        if (!empty($json->edit_templates)) {

            foreach ($json->edit_templates->templates as $template_record) {
                $template = [
                    'title'     =>  $template_record->title ?? "#{$edit_templates_index}",
                    'desc'      =>  $template_record->description ?? "#{$edit_templates_index}",
                    'url'       =>  "/storage/{$map_alias}/edit_templates/" . $template_record->url
                ];

                $edit_templates[] = $template;
                $edit_templates_index++;
            }

            if (!empty($json->edit_templates->content_css)) {
                $edit_templates_options['content_css'] = "/storage/{$map_alias}/edit_templates/" . $json->edit_templates->content_css;
            }

            $edit_templates_options['template_popup_width']
                = (!empty($json->edit_templates->template_popup_width))
                ? $json->edit_templates->template_popup_width
                : 800;

            $edit_templates_options['template_popup_height']
                = (!empty($json->edit_templates->template_popup_height))
                ? $json->edit_templates->template_popup_height
                : 400;
        }

        // конец блока заполнения edit-template
        // конец анализа json-конфига

        $t = new Template('edit.region.page.html', '$/templates/edit.region');
        $t->set('/', [
            'id_region'         =>  $region_id,
            'id_map'            =>  0,

            'title_map'         =>  $map_alias,    //@todo: тайтл карты загружать из БД или файла конфигурации карты

            'alias_map'         =>  $map_alias,

            'html_callback'     =>  "/map/{$map_alias}",

            'region_title'      =>  ($region_data['is_present'] == 1) ? htmlspecialchars($region_data['title'],  ENT_QUOTES | ENT_HTML5) : '',
            'region_text'       =>  $region_data['content'],
            'region_restricted' =>  htmlspecialchars($region_data['content_restricted'], ENT_QUOTES | ENT_HTML5),


            'is_present'        =>  $region_data['is_present'],      // 1 - регион существует, 0 - новый регион

            'is_logged_user'    =>  $userinfo['email'],
            'is_logged_user_ip' =>  $userinfo['ip'],

            'edit_templates'    =>  $edit_templates,
            'edit_templates_options' => $edit_templates_options,

            // copyright
            'copyright'         =>  Config::get('frontend_meta/title'),

            // revisions
            'region_revisions'  =>  $map_engine->getRegionRevisions( $map_alias, $region_id ),

            'is_exludelists'    =>  $region_data['is_exludelists'] ?? 'N',
            'is_publicity'      =>  $region_data['is_publicity'] ?? 'ANYONE',
        ]);











    }


}