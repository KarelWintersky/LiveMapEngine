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
        $this->template_path = '$/templates';
    }

    private function makemap_widecolorbox()
    {
        $this->template_file = 'view.map.wide-colorbox.html';
    }

    private function makemap_folio()
    {
        $this->template_file = 'view.map.folio.html';

    }

    private function makemap_colorbox()
    {
        $this->template_file = 'view.map.colorbox.html';
        // $this->template_file = 'view.map.wide-colorbox.html';

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

    private function makemap_iframe( /* \Template $tpl */)
    {
        $this->template_file = 'view.map.iframe.html';

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

        if ($skin === 'iframe') {

            $this->makemap_iframe();

        } elseif ($skin === 'colorbox') {

            $this->makemap_colorbox();

        } elseif ($skin === 'folio') {

            $this->makemap_folio();

        } else {
            $this->makemap_404( $skin );
            die( $this->template->render() );
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
