<?php

namespace Livemap\Controllers;

use AJUR\Template\Template;
use AJUR\Template\TemplateInterface;
use Arris\Path;
use Livemap\AbstractClass;
use Livemap\App;
use Livemap\Units\Map;
use Livemap\Units\MapConfig;
use Livemap\Units\SVGParser;
use Psr\Log\LoggerInterface;
use RuntimeException;
use stdClass;

class MapsController extends AbstractClass
{
    public function __construct($options = [], LoggerInterface $logger = null)
    {
        parent::__construct($options, $logger);
        $this->template->setTemplate("_public.view_maps.tpl");
    }

    /**
     *
     *
     * @param $map_alias
     * @return void
     * @throws \JsonException
     * @throws \SmartyException
     * @route  /map:js/alias.js
     */
    public function view_js_map_definition($map_alias)
    {
        $_map_config = new MapConfig($map_alias);
        $_map_config->loadConfig();
        $rConfig = $_map_config->getConfig(); //@todo: это надо упростить, должен возвращаться не Result, а stdClass JSON'а

        $json = $rConfig->__get('json');

        $image_info = array(
            'width'     =>  0,
            'height'    =>  0,
            'ox'        =>  0,
            'oy'        =>  0
        );
        $max_bounds = NULL;

        $regions_for_js = '';
        $paths_data = [];
        $layers_data = [];

        $LAYERS = [];

        try {
            if ($json->type === "vector" && empty($json->image)) {
                throw new RuntimeException( "[JS Builder] Declared vectorized image-layer, but image definition not found." );
            }

            $image_info = [];
            if (!empty($json->image)) {
                $image_info = [
                    'width'     =>  $json->image->width,
                    'height'    =>  $json->image->height,
                    'ox'        =>  $json->image->ox,
                    'oy'        =>  $json->image->oy
                ];
            }

            /* ============ SVG load ============= */
            if (empty($json->layout->file)) {
                throw new RuntimeException( "[JS Builder] Layout file not defined." );
            }
            $svg_filename = Path::create( getenv('PATH.STORAGE'))->join($map_alias)->joinName($json->layout->file)->toString();
            if (!is_file($svg_filename)) {
                throw new RuntimeException( "[JS Builder] Layout file {$svg_filename} not found." );
            }

            $svg_content = file_get_contents( $svg_filename );
            if ($svg_content === '') {
                throw new RuntimeException( "[JS Builder] Layout file is empty" );
            }
            /* =============== Layout ============ */
            // информация о слоях
            if (empty($json->layout)) {
                throw new RuntimeException( "[JS Builder] Layout data not found." );
            }

            // создаем инсанс парсера, передаем SVG-контент файла
            $sp = new SVGParser( $svg_content );
            if ($sp->svg_parsing_error) {
                throw new RuntimeException( "[JS Builder] SVG Parsing error ".$sp->svg_parsing_error[ 'message' ] );
            }
            // image layer from file
            // надо проверить наличие слоёв в массиве определений
            $layer_name = "Image";
            $sp->parseImages( $layer_name );

            if ($json->type === "bitmap" && $sp->getImagesCount()) {
                $image_info = $sp->getImageInfo();
                $sp->set_CRSSimple_TranslateOptions( $image_info['ox'], $image_info['oy'], $image_info['height'] );
            } else {
                $sp->set_CRSSimple_TranslateOptions( 0, 0, $image_info['height'] );
            }
            if (!empty($json->layout->layers)) {
                $layers_list = $json->layout->layers;
            } else {
                $layers_list[] = "Paths";
            }
            foreach($json->layout->layers as $layer) {
                // грузим конфиг по умолчанию из $json
                $defaults_empty = NULL;
                $defaults_present = NULL;
                $layer_config = NULL;

                /**
                 * @var stdClass $layer_config
                 */
                if (!empty($json->layers->$layer)) {
                    $layer_config = $json->layers->$layer;
                }

                $layers_data[] = [
                    'id'        =>  $layer,
                    'hint'      =>  $layer_config->hint,
                    'zoom_min'  =>  $layer_config->zoom_min ?? -100,
                    'zoom_max'  =>  $layer_config->zoom_max ?? 100
                ];

                $sp->parseLayer($layer);   // парсит слой (определяет атрибут трансформации слоя и конвертит в объекты все элементы)

                // установим конфигурационные значения для пустых регионов для текущего слоя
                $sp->setLayerDefaultOptions($layer_config);

                // получаем все элементы на слое
                $paths_at_layer = $sp->getElementsAll();

                // теперь нам нужны айдишники этих элементов на слое. Их надо проверить в БД и заполнить значениями кастомных полей из БД
                $paths_at_layers_ids = implode(", ", array_map( static function($item){
                    return "'{$item}'";
                }, array_keys($paths_at_layer)));

                // запросим БД на предмет кастомных значений и заполненности регионов
                // $lm_engine = new LiveMapEngine( LMEConfig::get_dbi() );
                $lm_engine = new Map();

                // технически в функцию надо отдавать МАССИВ, а превращать его в строку внутри функции
                $paths_at_layer_filled = Map::getRegionsWithInfo( $map_alias, $paths_at_layers_ids );

                // фильтруем по доступности пользователю (is_publicity)
                // $paths_at_layer_filled = $lm_engine->checkRegionsVisibleByUser($paths_at_layer_filled, $map_alias);
                $paths_at_layer_filled = Map::checkRegionsVisibleByCurrentUser($paths_at_layer_filled, $map_alias);

                foreach ($paths_at_layer_filled as $path_present) {
                    $id_region = $path_present['id_region'];

                    // если конфиг слоя определен
                    if ($layer_config) {

                        // это лишние данные, которые можно передать в настройках слоя

                        // если определены параметры заполнения региона
                        if ($layer_config->present->fill && $layer_config->present->fill == 1) {

                            if (!$path_present['fillColor'] && $layer_config->present->fillColor) {
                                $path_present['fillColor'] = $layer_config->present->fillColor;
                            }

                            if (!$path_present['fillOpacity'] && $layer_config->present->fillOpacity) {
                                $path_present['fillOpacity'] = $layer_config->present->fillOpacity;
                            }
                        }

                        // если определены параметры кастомной отрисовки границ региона
                        if ($layer_config->present->stroke && $layer_config->present->stroke == 1) {

                            if (!$path_present['borderColor'] && $layer_config->present->borderColor) {
                                $path_present['borderColor'] = $layer_config->present->borderColor;
                            }

                            if (!$path_present['borderWidth'] && $layer_config->present->borderWidth) {
                                $path_present['borderWidth'] = $layer_config->present->borderWidth;
                            }

                            if (!$path_present['borderOpacity'] && $layer_config->present->borderOpacity) {
                                $path_present['borderOpacity'] = $layer_config->present->borderOpacity;
                            }
                        }

                    } else {
                        // иначе, конфиг слоя не определен, используются глобальные дефолтные значения

                        if (!$path_present['fillColor']) {
                            $path_present['fillColor'] = $json->display_defaults->present->fillColor;
                        }

                        if (!$path_present['fillOpacity']) {
                            $path_present['fillOpacity'] = $json->display_defaults->present->fillOpacity;
                        }

                        if (!$path_present['borderColor']) {
                            $path_present['borderColor'] = $json->display_defaults->present->borderColor;
                        }

                        if (!$path_present['borderWidth']) {
                            $path_present['borderWidth'] = $json->display_defaults->present->borderWidth;
                        }

                        if (!$path_present['borderOpacity']) {
                            $path_present['borderOpacity'] = $json->display_defaults->present->borderOpacity;
                        }

                    }

                    $path_present['title'] = htmlspecialchars($path_present['title'], ENT_QUOTES | ENT_HTML5);
                    unset($path_present['edit_date']);

                    $paths_at_layer[ $id_region ] = array_merge($paths_at_layer[ $id_region ], $path_present);
                }

                $LAYERS[] = [
                    'id'        =>  $layer,
                    'hint'      =>  htmlspecialchars($layer_config->hint, ENT_QUOTES | ENT_HTML5),
                    'zoom'      =>  $layer_config->zoom ?? $json->display->zoom,
                    'zoom_min'  =>  $layer_config->zoom_min ?? -100,
                    'zoom_max'  =>  $layer_config->zoom_max ?? 100,
                    // 'regions'   =>  $paths_at_layer
                ];

                $paths_data += $paths_at_layer;
            }

            // maxbounds
            if (!empty($json->display->maxbounds)) {
                $max_bounds = [
                    'present'   =>  1,
                    'topleft_h'     =>  $json->display->maxbounds[0][0],
                    'topleft_w'     =>  $json->display->maxbounds[0][1],
                    'bottomright_h' =>  $json->display->maxbounds[1][0],
                    'bottomright_w' =>  $json->display->maxbounds[1][1]
                ];
            }
        } catch (\RuntimeException $e) {
            $this->ERROR = TRUE;
            $this->ERROR_MESSAGE = $e->getMessage();
        }

        $t = new Template(App::$smarty);
        $t->setTemplate("_js/theMapDefinition.tpl");

        if ($this->error) {
            $t->assign('/JSBuilderError', $this->ERROR_MESSAGE);
        }

        $t->assign("map", [
            'title'         =>  $json->title,
            'type'          =>  $json->type,
            'alias'         =>  $map_alias,
            'imagefile'     =>  $json->image->file,
            'width'         =>  $image_info['width'],
            'height'        =>  $image_info['height'],
            'ox'            =>  $image_info['ox'],
            'oy'            =>  $image_info['oy'],
        ]);
        $t->assign("display", [
            'zoom'                      =>  $json->display->zoom,
            'zoom_max'                  =>  $json->display->zoom_max,
            'zoom_min'                  =>  $json->display->zoom_min,
            'background_color'          =>  $json->display->background_color,
            'custom_css'                =>  $json->display->custom_css ?? '',
            'focus_animate_duration'    =>  $json->display->focus_animate_duration ?? 0.7,
            'focus_highlight_color'     =>  $json->display->focus_highlight_color ?? '#ff0000',
            'focus_timeout'             =>  $json->display->focus_timeout ?? 1000,
        ]);
        $t->assign('maxbounds', $max_bounds);
        $t->assign('region_defaults_empty', (array)$json->display_defaults->empty);
        $t->assign('region_defaults_present', (array)$json->display_defaults->present);

        $t->assign('layers', $LAYERS);
        $t->assign('regions', $paths_data);

        $content = $t->render();
        $content = preg_replace('/^\h*\v+/m', '', $content);

        $this->template->assignRAW($content);
        $this->template->sendHeader(TemplateInterface::CONTENT_TYPE_JS);
    }

    /**
     * Отрисовывает карту классического полноэкранного типа
     *
     * @param $map_alias
     * @route /map/alias
     */
    public function view_map_fullscreen($map_alias)
    {
        $this->mapConfig = (new MapConfig($map_alias))->loadConfig()->getConfig();
        $this->template->assign("inner_template", "view.map/view.map.fullscreen.tpl");

        $map = new Map();
        $map->loadConfig($map_alias);
        $map->loadMap($map_alias);

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
        $this->template->assign("inner_template", "view.map/view.map.fullscreen.tpl");

        $this->template->assign('map_alias', $map_alias);
        $this->template->assign('html_title', $this->mapConfig->title);
        $this->template->assign('html_callback', '/');

    }
}