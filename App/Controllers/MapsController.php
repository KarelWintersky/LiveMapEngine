<?php

namespace Livemap\Controllers;

use Arris\Path;
use Exception;
use Livemap\Template;
use Livemap\Units\Map;
use Livemap\Units\MapConfig;
use Livemap\Units\SVGParser;
use stdClass;

class MapsController
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
    private $valid_view_modes = [
        'colorbox', 'tabled:colorbox', 'folio', 'iframe', 'iframe:colorbox', 'wide:infobox>regionbox', 'wide:regionbox>infobox'
    ];
    
    public function __construct()
    {
        $this->unit = new Map();
        $this->template_path = 'templates/view.map/';
        
        
    }
    
    /**
     * Отрисовывает карту классического полноэкранного типа
     *
     * @param $map_alias
     * @return string
     * @throws \Exception
     */
    public function view_map_fullscreen($map_alias)
    {
        // $viewmode = 'wide:infobox>regionbox';
    
        if (!empty($map_config->display->viewmode)) {
            $viewmode = $map_config->display->viewmode;
        }
        // перекрываем его из $_GET
        $viewmode = filter_array_for_allowed($_GET, 'viewmode', $this->valid_view_modes, $viewmode);
        $viewmode = filter_array_for_allowed($_GET, 'view',     $this->valid_view_modes, $viewmode);
        
        $map_config = [];
        
        Template::assign('map_alias', $map_alias);
        
        if (!empty($this->map_config->display->custom_css)) {
            Template::assign('custom_css', "/storage/{$map_alias}/styles/{$this->map_config->display->custom_css}");
        }
        Template::assign('panning_step', $this->map_config->display->panning_step ?? 70);
        Template::assign('html_title', $this->map_config->title);
        Template::assign('html_callback', '/');
        
        // извлекает все регионы с информацией
        $this->map_regions_with_info = $this->unit->getRegionsWithInfo( $map_alias, []);
    
        // фильтруем по доступности пользователю (is_publicity)
        $this->map_regions_with_info = Map::checkRegionsVisibleByCurrentUser($this->map_regions_with_info, $map_alias);
    
        // фильтруем по visibility
        $this->map_regions_with_info = Map::removeExcludedFromRegionsList($this->map_regions_with_info);
        Template::assign('regions_with_content_ids', Map::convertRegionsWithInfo_to_IDs_String($this->map_regions_with_info));
    
        $regions_with_data_order_by_title = $this->map_regions_with_info;
        usort($regions_with_data_order_by_title, function($value1, $value2){
            return ($value1['title'] > $value2['title']);
        });
        $regions_with_data_order_by_date = $this->map_regions_with_info;
        usort($regions_with_data_order_by_date, function($value1, $value2){
            return ($value1['edit_date'] < $value2['edit_date']);
        });
        
        Template::assign('map_regions_order_by_title', $regions_with_data_order_by_title);
        Template::assign('map_regions_order_by_date', $regions_with_data_order_by_date);
        Template::assign('map_regions_count', count($this->map_regions_with_info));
        
        if ($viewmode === 'wide:infobox>regionbox') {
            Template::assign('section', [
                'infobox_control_position'      =>  'topleft',
                'regionbox_control_position'    =>  'topright',
                'regionbox_textalign'           =>  'right'
            ]);
        } else {
            Template::assign('section', [
                'infobox_control_position'      =>  'topright',
                'regionbox_control_position'    =>  'topleft',
                'regionbox_textalign'           =>  'left'
            ]);
        }
        
        return Template::render('view.map/view.map.fullscreen.tpl');
    }

    /**
     * Отрисовывает карту в ифрейме: попап сделан через колорбокс посередине экрана
     *
     * @param $map_alias
     * @return string
     */
    public function view_map_iframe($map_alias)
    {
        return "Show iframe map <strong>{$map_alias}</strong> in IFRAME";
    }


    /**
     * Отрисовывает in-folio карту - без информационных окон
     *
     * @param $map_alias
     * @return string
     */
    public function view_map_folio($map_alias) {
        return "Show folio map <strong>{$map_alias}</strong> as FOLIO";
    }
    
    /**
     * @throws \Exception
     */
    public function get_js_map_definition($map_alias)
    {
        $_map_config = new MapConfig($map_alias);
        $_map_config->loadConfig();
        $json = $_map_config->getConfig();
    
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
                throw new Exception( "[JS Builder] Declared vectorized image-layer, but image definition not found." );
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
                throw new Exception( "[JS Builder] Layout file not defined." );
            }
            $svg_filename = Path::create( getenv('PATH.STORAGE'))->join($map_alias)->joinName($json->layout->file)->toString();
            if (!is_file($svg_filename)) {
                throw new Exception( "[JS Builder] Layout file {$svg_filename} not found." );
            }
    
            $svg_content = file_get_contents( $svg_filename );
            if (strlen($svg_content) == 0) {
                throw new Exception( "[JS Builder] Layout file is empty" );
            }
            /* =============== Layout ============ */
            // информация о слоях
            if (empty($json->layout)) {
                throw new Exception( "[JS Builder] Layout data not found." );
            }
    
            // создаем инсанс парсера, передаем SVG-контент файла
            $sp = new SVGParser( $svg_content );
            if ($sp->svg_parsing_error) {
                throw new Exception( "[JS Builder] SVG Parsing error ".$sp->svg_parsing_error[ 'message' ] );
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
                $paths_at_layer_filled = $lm_engine->getRegionsWithInfo( $map_alias, $paths_at_layers_ids );
        
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
        } catch (\Exception $e) {
            $this->ERROR = TRUE;
            $this->ERROR_MESSAGE = $e->getMessage();
            // if (DEBUG) var_dd($e);
        }
        // теперь генерируем подстановочные значения для шаблона
        if ($this->ERROR) {
            Template::assign('/JSBuilderError', $this->ERROR_MESSAGE);
        }
        Template::assign('map', [
            'title'         =>  $json->title,
            'type'          =>  $json->type,
            'alias'         =>  $map_alias,
            'imagefile'     =>  $json->image->file,
            'width'         =>  $image_info['width'],
            'height'        =>  $image_info['height'],
            'ox'            =>  $image_info['ox'],
            'oy'            =>  $image_info['oy'],
        ]);
        Template::assign('display', [
            'zoom'                      =>  $json->display->zoom,
            'zoom_max'                  =>  $json->display->zoom_max,
            'zoom_min'                  =>  $json->display->zoom_min,
            'background_color'          =>  $json->display->background_color,
            'custom_css'                =>  $json->display->custom_css ?? '',
            'focus_animate_duration'    =>  $json->display->focus_animate_duration ?? 0.7,
            'focus_highlight_color'     =>  $json->display->focus_highlight_color ?? '#ff0000',
            'focus_timeout'             =>  $json->display->focus_timeout ?? 1000,
    
        ]);
        Template::assign('maxbounds', $max_bounds);
    
        Template::assign('region_defaults_empty', (array)$json->display_defaults->empty);
        Template::assign('region_defaults_present', (array)$json->display_defaults->present);
    
        Template::assign('layers', $LAYERS);
        Template::assign('regions', $paths_data);
        
        $content = Template::render('js/js.tpl');
        return preg_replace('/^\h*\v+/m', '', $content);
    }
}