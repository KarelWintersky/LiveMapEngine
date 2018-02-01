<?php

/**
 * Class MapRender
 */
class MapRender extends UnitPrototype
{
    /**
     * Возможные типы курсоров
     * @var array
     */
    private $allowed_cursors = [
        'auto', 'default', 'none', 'context-menu', 'help', 'pointer', 'progress', 'wait', 'cell', 'crosshair',
        'text', 'vertical-text', 'alias', 'copy', 'move', 'no-drop', 'not-allowed', 'all-scroll', 'col-resize',
        'row-resize', 'n-resize', 's-resize', 'e-resize', 'w-resize', 'ns-resize', 'ew-resize', 'ne-resize',
        'nw-resize', 'se-resize', 'sw-resize', 'nesw-resize', 'nwse-resize'
    ];

    /**
     * @var Template $template
     */
    private $template;
    private $map_alias;

    private $template_file = '';
    private $template_path = '';

    /**
     * @var stdClass $map_config
     */
    private $map_config = NULL;

    /**
     * Массив регионов с информацией
     * @var array
     */
    private $map_regions_with_info = [];


    /**
     * Информация о карте (массив?)
     * @var null
     */
    private $map_info = NULL;


    /**
     * @var \LiveMapEngine $lme;
     */
    private $lme;


    /* ================================= */

    public function __construct($map_alias, $map_config = null)
    {
        $this->map_alias = $map_alias;
        $this->map_config = $map_config;

        $this->template_file = '';
        $this->template_path = '$/templates/view.map';

        $this->lme = new LiveMapEngine( LMEConfig::get_dbi() );

        $this->map_info = $this->lme->getMapInfo( $this->map_alias );
    }

    public function run( $viewmode = 'folio') {
        $this->template = new Template('', $this->template_path);
        $this->template->set('/map_alias', $this->map_alias);

        if (!empty($this->map_config->display->custom_css)) {
            $this->template->set('custom_css', "/storage/{$this->map_alias}/styles/{$this->map_config->display->custom_css}");
        }

        $this->template->set('/panning_step', $this->map_config->display->panning_step ?? 70);
        $this->template->set('/html/title', $this->map_config->title);
        $this->template->set('/html_callback', '/');

        // извлекаетм все регионы с информацией
        $this->map_regions_with_info = $this->lme->getRegionsWithInfo( $this->map_alias );

        // фильтруем по доступности пользователю (is_publicity)
        $this->map_regions_with_info = $this->lme->checkRegionsVisibleByUser($this->map_regions_with_info, $this->map_alias);

        // фильтруем по visibility
        $this->map_regions_with_info = $this->lme->removeExcludedFromRegionsList($this->map_regions_with_info);



        $this->template->set('/regions_with_content_ids', $this->lme->convertRegionsWithInfo_to_IDs_String($this->map_regions_with_info));

        switch ($viewmode) {
            case 'iframe': {
                $this->makemap_iframe_colorbox();
                break;
            }

            case 'iframe:colorbox': {
                $this->makemap_iframe_colorbox();
                break;
            }

            case 'folio': {
                $this->makemap_folio();
                break;
            }

            case 'wide:infobox>regionbox': {
                $this->makemap_fullscreen('infobox>regionbox');
                break;
            }

            case 'wide:regionbox>infobox': {
                $this->makemap_fullscreen('regionbox>infobox');
                break;
            }

            default: {
                $this->makemap_404( $viewmode );
                die( $this->template->render() );
            }
        } // switch $viewmode

        return true;
    }


    private function makemap_fullscreen( $orientation )
    {
        $this->template_file = 'view.map.fullscreen.html';

        $regions_with_data_order_by_title = $this->map_regions_with_info;

        usort($regions_with_data_order_by_title, function($value1, $value2){
            return ($value1['title'] > $value2['title']);
        });

        $regions_with_data_order_by_date = $this->map_regions_with_info;
        usort($regions_with_data_order_by_date, function($value1, $value2){
            return ($value1['edit_date'] < $value2['edit_date']);
        });

        $this->template->set('/', array(
            'map_regions_order_by_title'    =>  $regions_with_data_order_by_title,
            'map_regions_order_by_date'     =>  $regions_with_data_order_by_date,
            'map_regions_count'             =>  count($this->map_regions_with_info),
        ));

        if ($orientation === 'infobox>regionbox') {
            $this->template->set('/section/infobox_control_position', 'topleft');
            $this->template->set('/section/regionbox_control_position', 'topright');
            $this->template->set('/section/regionbox_textalign', 'right');

        } else {
            $this->template->set('/section/infobox_control_position', 'topright');
            $this->template->set('/section/regionbox_control_position', 'topleft');
            $this->template->set('/section/regionbox_textalign', 'left');
        }


    }

    private function makemap_folio()
    {
        $this->template_file = 'view.map.folio.html';
    }

    private function makemap_iframe_colorbox()
    {
        $this->template_file = 'view.map.iframe_colorbox.html';

        $this->template->set('map_viewport_width', filter_input(INPUT_GET, 'width', FILTER_VALIDATE_INT) ?? 800);
        $this->template->set('map_viewport_height', filter_input(INPUT_GET, 'height', FILTER_VALIDATE_INT) ?? 600);
    }


    private function viewport_get_map_cursor()
    {
        $cursor_style = '';
        if (!empty($this->map_config->display->cursor)) {

            $cursor_style
                = in_array($this->map_config->display->cursor, $this->allowed_cursors)
                ?  $this->map_config->display->cursor
                : 'pointer';
            $cursor_style = " cursor:{$cursor_style}; ";
        }

        return $cursor_style;
    }

    /**
     * @param $skin
     */
    private function makemap_404( $skin )
    {
        $this->template_file = '404.html';
        $this->template_path = '$/templates';
        $this->template->set('error_message', "Unknown skin mode {$skin} for map {$this->map_alias}");
    }

    public function content()
    {
        if (method_exists($this->template, 'render')) {
            $this->template->setTemplateFile( $this->template_file );
            $this->template->setTemplatePath( $this->template_path );
            return $this->template->render();
        }

    }
}
