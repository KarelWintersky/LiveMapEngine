<?php

namespace Livemap\Controllers;

use Arris\AppLogger;
use Arris\Path;
use Livemap\App;
use Livemap\Template;
use Livemap\Units\Map;
use PDO;

class RegionsController
{
    /**
     * @var PDO
     */
    private $pdo;
    
    /**
     * @var AppLogger
     */
    private $logger;
    
    public function __construct()
    {
        $this->pdo = App::factory()->pdo;
        $this->logger = AppLogger::scope('main');
    }
    
    /**
     * @throws \Exception
     */
    public function view_page_edit_region($map_alias, $region_id) {
        
        // $auth = Auth::getInstance();
        // $userinfo = $auth->getCurrentUser();
        
        // проверяем права редактирования
        // $current_role = ACL::getRole($userinfo['uid'], $map_alias);
        // $can_edit = ACL::isValidRole($current_role, 'EDITOR');
        $can_edit = true;
        
        // Это задача middleware
        if (!$can_edit) {
            die('Not enough access rights for update info!');
        }
        
        setcookie( getenv('AUTH.COOKIES.FILEMANAGER_STORAGE_PATH'), $map_alias, 0, '/');
        setcookie( getenv('AUTH.COOKIES.FILEMANAGER_CURRENT_MAP'), $map_alias, 0, '/');
        
        $map_engine = new Map();
        $region_data = $map_engine->getMapRegionData($map_alias, $region_id);
        
        // читаем шаблоны редактирования из json-файла конфигурации карты (это должно быть где-то в LivemapFramework)
        $filename = Path::create( getenv('PATH.STORAGE') )->join($map_alias)->joinName('index.json')->toString();
        if (!is_file($filename)) {
            throw new \RuntimeException("Map definition file not found, requested {$filename}");
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
        
        Template::setGlobalTemplate('edit.region/edit.region.page.html');
        $dataset = [
            'id_region'         =>  $region_id,
            'id_map'            =>  0,
            
            'title_map'         =>  $map_alias,    //@todo: тайтл карты загружать из БД или файла конфигурации карты
            
            'alias_map'         =>  $map_alias,
            
            'html_callback'     =>  "/map/{$map_alias}",
            
            'region_title'      =>  ($region_data['is_present'] == 1) ? htmlspecialchars($region_data['title'],  ENT_QUOTES | ENT_HTML5) : '',
            'region_text'       =>  $region_data['content'],
            'region_restricted' =>  htmlspecialchars($region_data['content_restricted'], ENT_QUOTES | ENT_HTML5),
            
            
            'is_present'        =>  $region_data['is_present'],      // 1 - регион существует, 0 - новый регион
            
            'is_logged_user'    =>  $userinfo['email'] ?? '',
            'is_logged_user_ip' =>  $userinfo['ip'] ?? '',
            
            'edit_templates'            =>  $edit_templates,
            'edit_templates_options'    => $edit_templates_options,
            
            // copyright
            'copyright'         =>  getenv('COPYRIGHT'),
            
            // revisions
            'region_revisions'  =>  $map_engine->getRegionRevisions( $map_alias, $region_id ),
            
            'is_exludelists'    =>  $region_data['is_exludelists'] ?? 'N',
            'is_publicity'      =>  $region_data['is_publicity'] ?? 'ANYONE',
        ];
        foreach ($dataset as $k => $v) {
            Template::assign($k, $v);
        }
        
        
    }
    
}