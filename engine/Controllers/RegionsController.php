<?php

namespace Livemap\Controllers;

use AJUR\Template\Template;
use AJUR\Template\TemplateInterface;
use Arris\AppRouter;
use Arris\Path;
use Livemap\AbstractClass;
use Livemap\App;
use Livemap\Exceptions\AccessDeniedException;
use Livemap\Units\ACL;
use Livemap\Units\MapLegacy;

#[AllowDynamicProperties]
class RegionsController extends AbstractClass
{
    /**
     * Показать информацию по региону
     *
     * @return void
     * @throws \JsonException
     * @throws \SmartyException
     */
    public function view_region_info()
    {
        $map_alias = $_GET['map']   ?? null;
        $id_region = $_GET['id']    ?? null;
        $template  = $_GET['resultType'] ?? 'html';

        $region_data = (new MapLegacy())->getMapRegionData($map_alias, $id_region);

        $t = new Template(App::$smarty);
        $t->assign('is_present', $region_data['is_present']);
        $t->assign('map_alias', $map_alias);
        $t->assign('region_id', $id_region);
        $t->assign('region_title', $region_data['title']);
        $t->assign('region_text', $region_data['content']);
        $t->assign('is_can_edit', $region_data['can_edit']);
        $t->assign('edit_button_url', AppRouter::getRouter('edit.region.info'));

        switch ($template) {
            case 'iframe': {
                $t->setTemplate('view.region/view.region.iframe.tpl');;
                break;
            }
            case 'fieldset': {
                $t->setTemplate('view.region/view.region.fieldset.tpl');
                break;
            }
            default: {
                $t->setTemplate('view.region/view.region.html.tpl');
                break;
            }
        }

        $content = $t->render();

        $this->template->assignRAW($content);
    }

    public function view_region_edit_form()
    {
        // $auth = Auth::getInstance();
        // $userinfo = $auth->getCurrentUser();

        // проверяем права редактирования
        // $current_role = ACL::getRole($userinfo['uid'], $map_alias);
        // $can_edit = ACL::isValidRole($current_role, 'EDITOR');

        // Это задача middleware
        $map_alias = $_REQUEST['map'];
        $region_id = $_REQUEST['id'];

        $map_engine = new MapLegacy();
        $region_data = $map_engine->getMapRegionData($map_alias, $region_id);

        $filename
            = Path::create( getenv('PATH.STORAGE') )
            ->join($map_alias)
            ->joinName('index.json5')
            ->toString();

        if (!is_file($filename)) {
            throw new \RuntimeException("Map definition file not found, requested {$filename}");
        }

        $can_edit = ACL::simpleCheckCanEdit($map_alias);
        if (!$can_edit) {
            throw new AccessDeniedException("Обновление региона недоступно, недостаточный уровень допуска");
        }

        $json = json5_decode( file_get_contents( $filename ) );

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

        $this->template->setTemplate('edit.region/edit.region.page.tpl');

        $this->template->assign([
            'id_region'         =>  $region_id,
            'id_map'            =>  0,

            'form_actor'        =>  AppRouter::getRouter('update.region.info'),

            'title_map'         =>  $map_alias,    //@todo: тайтл карты загружать из БД или файла конфигурации карты

            'alias_map'         =>  $map_alias,

            'html_callback'     =>  "/map/{$map_alias}/",

            'region_title'      =>  ($region_data['is_present'] == 1) ? htmlspecialchars($region_data['title'],  ENT_QUOTES | ENT_HTML5) : '',
            'region_text'       =>  $region_data['content'],
            'region_restricted' =>  htmlspecialchars($region_data['content_restricted'] ?? '', ENT_QUOTES | ENT_HTML5),


            'is_present'        =>  $region_data['is_present'],      // 1 - регион существует, 0 - новый регион

            'is_logged_user'    =>  config('auth.username'),
            'is_logged_user_ip' =>  config('auth.ipv4'),

            'edit_templates'            =>  $edit_templates,
            'edit_templates_options'    => $edit_templates_options,

            // copyright
            'copyright'         =>  config('app.copyright'),

            // revisions
            // 'region_revisions'  =>  $map_engine->getRegionRevisions( $map_alias, $region_id ),

            'is_exludelists'    =>  $region_data['is_exludelists'] ?? 'N',
            'is_publicity'      =>  $region_data['is_publicity'] ?? 'ANYONE',
        ]);

        // ставим куки для файлменеджера
        setcookie( getenv('AUTH.COOKIES.FILEMANAGER_STORAGE_PATH'), $map_alias, 0, '/');
        setcookie( getenv('AUTH.COOKIES.FILEMANAGER_CURRENT_MAP'), $map_alias, 0, '/');
    }

    /**
     *
     * @return void
     */
    public function callback_update_region()
    {
        $map_alias = $_REQUEST['edit:alias:map'];
        $region_id = $_REQUEST['edit:id:region'];

        $result = (new MapLegacy())->storeMapRegionData($map_alias, $region_id, $_REQUEST);

        $this->template->assignRAW($result->serialize());
        $this->template->sendHeader(TemplateInterface::CONTENT_TYPE_JS);
    }

}