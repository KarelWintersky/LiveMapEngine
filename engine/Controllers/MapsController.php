<?php

namespace Livemap\Controllers;

use AJUR\Template\Template;
use AJUR\Template\TemplateInterface;
use AllowDynamicProperties;
use Arris\AppRouter;
use Arris\Entity\Result;
use Arris\Helpers\Server;
use Livemap\AbstractClass;
use Livemap\App;
use Livemap\Exceptions\AccessDeniedException;
use Livemap\OpenGraph;
use Livemap\Units\ACL;
use Livemap\Units\MapLegacy;
use Livemap\Units\MapConfig;
use Psr\Log\LoggerInterface;

#[AllowDynamicProperties]
class MapsController extends AbstractClass
{
    private MapLegacy $engine;

    public function __construct($options = [], LoggerInterface $logger = null)
    {
        parent::__construct($options, $logger);
        $this->template->setTemplate("_map.tpl");

        $this->engine = new MapLegacy();
    }

    public function view_map_edit_form()
    {
        $map_alias = $_REQUEST['map'];

        $map_about = $this->engine->getMapAbout($map_alias);

        $this->template->setTemplate('map/edit.map.about.tpl');

        $this->template->assign([
            'id_map'            =>  0,
            'form_actor'        =>  AppRouter::getRouter('update.map.about'),
            'alias_map'         =>  $map_alias,
            'html_callback'     =>  "/map/{$map_alias}/",

            'title_map'         =>  $map_alias,

            'content'           =>  $map_about['content'] ?? '',
            'title'             =>  $map_about['title'] ?? '',

            'is_present'        =>  !empty($map_about),

            'is_logged_user'    =>  config('auth.username'),
            'is_logged_user_ip' =>  config('auth.ipv4'),

            // copyright
            'copyright'         =>  config('app.copyright'),
        ]);

        setcookie( getenv('AUTH.COOKIES.FILEMANAGER_STORAGE_PATH'), $map_alias, 0, '/');
        setcookie( getenv('AUTH.COOKIES.FILEMANAGER_CURRENT_MAP'), $map_alias, 0, '/');

    }

    public function callback_update_map()
    {
        $map_alias = $_REQUEST['edit:alias:map'];
        $result = new Result();

        $query = "
INSERT INTO map_about 
    (alias_map, edit_whois, edit_ipv4, title, content, edit_comment, is_publicity)
VALUES
    (:alias_map, :edit_whois, :edit_ipv4, :title, :content, :edit_comment, :is_publicity)
";
        $data = [
            'alias_map'     =>  $map_alias,
            'edit_whois'    =>  config('auth.id') ?? 0,
            'edit_ipv4'     =>  ip2long(Server::getIP()),
            'title'         =>  $_REQUEST['edit:map:title'] ?? '',
            'content'       =>  $_REQUEST['edit:map:about'] ?? '',
            'edit_comment'  =>  $_REQUEST['edit:comment'],
            'is_publicity'  =>  $_REQUEST['edit:is:publicity']
        ];

        try {
            $sth = $this->pdo->prepare($query);
            $sth->execute($data);
        } catch (\PDOException $e) {
            $result->error($e->getMessage());
        }

        $this->template->assignRAW($result->serialize());
        $this->template->sendHeader(TemplateInterface::CONTENT_TYPE_JS);
    }

    public function view_map_about()
    {
        $map_alias = $_REQUEST['map'];

        $map_about = $this->engine->getMapAbout($map_alias);

        $t = new Template(App::$smarty);
        $t->assign('map_alias', $map_alias);
        $t->assign('is_can_edit', \Arris\config('auth.is_admin'));
        $t->assign("edit_button_url", AppRouter::getRouter('edit.map.about'));

        $t->assign("is_present", !empty($map_about));
        $t->assign("title", $map_about['title'] ?? '');
        $t->assign("content", $map_about['content'] ?? '');

        $t->setTemplate('map/view.map.about.tpl');

        $content = $t->render();

        $this->template->assignRAW($content);
    }

    /**
     * Отрисовывает карту классического полноэкранного типа
     *
     * @param $map_alias
     * @route /map/alias
     */
    public function view_map_fullscreen($map_alias)
    {
        $mc = new MapConfig($map_alias);
        $mc->loadConfig();

        $this->mapConfig = $mc->getConfig();

        $map = new MapLegacy();
        $map->loadConfig($map_alias);
        $map->loadMap($map_alias);

        $this->template->assign("og", OpenGraph::makeForMap($map_alias, $this->mapConfig));

        // assign data
        $this->template->assign('map_alias', $map_alias);

        if (!empty($this->mapConfig->display->custom_css)) {
            $this->template->assign('custom_css', "/storage/{$map_alias}/styles/{$this->mapConfig->display->custom_css}");
        }

        $this->template->assign('panning_step', $map->mapConfig->display->panning_step ?? 70);


        $this->template->assign('html_title', $map->mapConfig->title);
        $this->template->assign('html_callback', '/');

        $this->template->assign('regions_with_content_ids', $map->mapRegionsWithInfo_IDS);

        $this->template->assign('map_regions_order_by_title', $map->mapRegionWithInfoOrderByTitle);
        $this->template->assign('map_regions_order_by_date', $map->mapRegionWithInfoOrderByDate);
        $this->template->assign('map_regions_count', count($map->mapRegionsWithInfo));

        // может быть перекрыто настройкой из конфига.
        $this->template->assign("sections_present", [
            'infobox'   =>  true,
            'regions'   =>  true && ( $this->mapConfig->display->sections->regions ?? true ),
            'backward'  =>  true && ( $this->mapConfig->display->sections->backward ?? true ),
            'title'     =>  false,
            'colorbox'  =>  true,
            'about'     =>  $this->mapConfig->display->about ?? ''
        ]);

        // Backward имеет нестандартное определение в конфиге (непустое)
        $backward_buttons = [];
        if (!empty($this->mapConfig->display->sections->backward)) {
            foreach ((array)$this->mapConfig->display->sections->backward as $backward_element) {
                $backward_buttons[] = [
                    'text'  =>  $backward_element->{'text'} ?? 'Назад',
                    'link'  =>  $backward_element->{'link'} ?? '/'
                ];
            }
        }
        $this->template->assign("section_backward_content", $backward_buttons);

        // главный обслуживающий скрипт
        $this->template->assign('main_js_file', '/frontend/view.map.fullscreen.js');
        $this->template->assign('main_css_file', '/frontend/view.map.fullscreen.css');

        if ($map->mapViewMode === 'wide:infobox>regionbox' || $map->mapViewMode === 'infobox>regionbox') {
            $this->template->assign('section', [
                'infobox_control_position'      =>  'topleft',
                'regionbox_control_position'    =>  'topright',
                'regionbox_textalign'           =>  'right'
            ]);
        } else {
            $this->template->assign('section', [
                'infobox_control_position'      =>  'topright',
                'regionbox_control_position'    =>  'topleft',
                'regionbox_textalign'           =>  'left'
            ]);
        }
    }

    /**
     * @param $map_alias
     * @return void
     */
    public function view_iframe($map_alias)
    {
        $this->mapConfig = (new MapConfig($map_alias))->loadConfig()->getConfig();
        $this->template->assign("inner_template", "view.map/view.map.iframe_colorbox.tpl");

        $this->template->assign('map_alias', $map_alias);
        $this->template->assign('html_title', $this->mapConfig->title);
        $this->template->assign('html_callback', '/');

        $this->template->assign("og", OpenGraph::makeForMap($map_alias, $this->mapConfig));

        $this->template->assign("sections_present", [
            'infobox'   =>  false,
            'regions'   =>  false,
            'backward'  =>  true && ( $this->mapConfig->display->sections->backward ?? true ),
            'title'     =>  false,
            'colorbox'  =>  true && ( $this->mapConfig->display->sections->colorbox ?? true ),
        ]);
        // главный обслуживающий скрипт
        $this->template->assign('main_js_file', '/frontend/view.map.iframe_colorbox.js');
        $this->template->assign('main_css_file', '/frontend/view.map.fullscreen.css');
    }

    /**
     * Отрисовывает in-folio карту - без информационных окон
     *
     * @param $map_alias
     * @return void
     */
    public function view_map_folio($map_alias)
    {
        $this->mapConfig = (new MapConfig($map_alias))->loadConfig()->getConfig();

        $this->template->assign('map_alias', $map_alias);
        $this->template->assign('html_title', $this->mapConfig->title);
        $this->template->assign('html_callback', '/');

        $this->template->assign("og", OpenGraph::makeForMap($map_alias, $this->mapConfig));

        $this->template->assign("sections_present", [
            'infobox'   =>  false,
            'regions'   =>  false,
            'backward'  =>  true && ( $this->mapConfig->display->sections->backward ?? true ),
            'title'     =>  true && ( $this->mapConfig->display->sections->title ?? true ),
            'colorbox'  =>  false,
        ]);
        // главный обслуживающий скрипт
        $this->template->assign('main_js_file', '/frontend/view.map.folio.js');
        $this->template->assign('main_css_file', '/frontend/view.map.folio.css');
    }

    // @todo: добавить интерактивность, в частности, colorbox с информацией из SVG-атрибутов для folio:interactive

}