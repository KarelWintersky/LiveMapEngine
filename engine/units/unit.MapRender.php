<?php

/**
 * Class MapRender
 */
class MapRender extends UnitPrototype
{
    /**
     * @var Template $template
     */
    private $template;
    private $map_alias;

    private $template_file = '';
    private $template_path = '';

    public function __construct($map_alias)
    {
        $this->map_alias = $map_alias;

        $this->template_file = '';
        $this->template_path = '$/templates/view.map';
    }

    private function makemap_widemap( $orientation )
    {
        if ($orientation === 'infobox>regionbox') {
            $this->template_file = 'view.map.wide_left=info_right=region.html';
        } else {
            $this->template_file = 'view.map.wide_left=region_right=info.html';
        }

        $lm_engine = new LiveMapEngine( LMEConfig::get_dbi() );

        $regions_with_data = $lm_engine->getRegionsWithInfo( $this->map_alias );

        $regions_with_data_order_by_title = $regions_with_data;
        usort($regions_with_data_order_by_title, function($value1, $value2){
            return ($value1['title'] > $value2['title']);
        });

        $regions_with_data_order_by_date = $regions_with_data;
        usort($regions_with_data_order_by_date, function($value1, $value2){
            return ($value1['edit_date'] < $value2['edit_date']);
        });

        $map_info = $lm_engine->getMapInfo( $this->map_alias );

        $this->template->set('/', array(
            // 'target'                        =>  filter_array_for_allowed($_GET, 'target', array('iframe', 'tiddlywiki'), FALSE),
            'map_regions_with_info_jsarray' =>  $lm_engine->convertRegionsWithInfo_to_IDs_String( $regions_with_data ),
            'map_regions_order_by_title'    =>  $regions_with_data_order_by_title,
            'map_regions_order_by_date'     =>  $regions_with_data_order_by_date,
            'map_regions_count'             =>  count($regions_with_data)

            // map
            // тайтл карты и настройки мы должны брать из таблицы settings_map
            // но сейчас она не заполняется никак и все данные берутся из json-файла настроек или SVG-файла разметки
        ));


    }

    private function makemap_folio()
    {
        $this->template_file = 'view.map.folio.html';

    }

    private function makemap_tabled_colorbox()
    {
        $this->template_file = 'view.map.tabled_colorbox.html';

        $lm_engine = new LiveMapEngine( LMEConfig::get_dbi() );

        $regions_with_data = $lm_engine->getRegionsWithInfo( $this->map_alias );

        $regions_with_data_order_by_title = $regions_with_data;
        usort($regions_with_data_order_by_title, function($value1, $value2){
            return ($value1['title'] > $value2['title']);
        });

        $regions_with_data_order_by_date = $regions_with_data;
        usort($regions_with_data_order_by_date, function($value1, $value2){
            return ($value1['edit_date'] < $value2['edit_date']);
        });

        $this->template->set('map_viewport_width', filter_input(INPUT_GET, 'width', FILTER_VALIDATE_INT) ?? 800);
        $this->template->set('map_viewport_height', filter_input(INPUT_GET, 'height', FILTER_VALIDATE_INT) ?? 600);

        $this->template->set('/', array(
            // 'target'                        =>  filter_array_for_allowed($_GET, 'target', array('iframe', 'tiddlywiki'), FALSE),
            'map_regions_with_info_jsarray' =>  $lm_engine->convertRegionsWithInfo_to_IDs_String( $regions_with_data ),
            'map_regions_order_by_title'    =>  $regions_with_data_order_by_title,
            'map_regions_order_by_date'     =>  $regions_with_data_order_by_date,
            'map_regions_count'             =>  count($regions_with_data)
        ));
    }

    private function makemap_iframe()
    {
        $this->template_file = 'view.map.iframe_colorbox.html';

        $lm_engine = new LiveMapEngine( LMEConfig::get_dbi() );

        $regions_with_data = $lm_engine->getRegionsWithInfo( $this->map_alias );

        $this->template->set('map_viewport_width', filter_input(INPUT_GET, 'width', FILTER_VALIDATE_INT) ?? 800);
        $this->template->set('map_viewport_height', filter_input(INPUT_GET, 'height', FILTER_VALIDATE_INT) ?? 600);

        $this->template->set('/', array(
            'map_regions_with_info_jsarray' =>  $lm_engine->convertRegionsWithInfo_to_IDs_String( $regions_with_data),
        ));
    }

    public function run( $skin = 'colorbox' )
    {
        $this->template = new Template('', $this->template_path);
        $this->template->set('/map_alias', $this->map_alias);

        switch ($skin) {
            case 'iframe': {
                $this->makemap_iframe();
                break;
            }
            case 'iframe:colorbox': {
                $this->makemap_iframe();
                break;
            }

            case 'tabled:colorbox' : {
                $this->makemap_tabled_colorbox();
                break;
            }
            case 'folio': {
                $this->makemap_folio();
                break;
            }

            case 'wide:infobox>regionbox': {
                // infobox left, regionbox right
                $this->makemap_widemap('infobox>regionbox');
                break;
            }
            case 'wide:regionbox>infobox': {
                // regionbox left, infobox righr
                $this->makemap_widemap('regionbox>infobox');
                break;
            }
            default: {
                $this->makemap_404( $skin );
                die( $this->template->render() );
            }
        }

        $this->template->set('html_callback', '/');
        return true;
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
