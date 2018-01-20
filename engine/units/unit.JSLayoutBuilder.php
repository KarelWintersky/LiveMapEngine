<?php
/**
 * User: Arris
 * Date: 16.01.2018, time: 9:22
 */
class JSLayoutBuilder extends UnitPrototype {

    /**
     * @param DBConnectionLite $db_instance
     */
    // private $db_instance;

    // private $db_table_prefix = '';

    /**
     * Результирующий шаблон
     * @var
     */
    private $template;

    /**
     * Файл шаблона
     * @var string
     */
    private $template_file = '';

    /**
     * Путь до файла шаблона
     * @var string
     */
    private $template_path = '';

    /**
     * имя-алиас карты
     * @var
     */
    private $map_alias;

    /**
     * Тип источника данных для конфига
     * @var string
     */
    private $config_type;


    /**
     * Имя файла конфига
     * @var string
     */
    private $json_config_filename = '';

    /**
     * Контент файла конфига, строкой
     * @var string
     */
    private $json_config_content = '';

    /**
     * JSON config file decoded
     * @var object
     */
    private $json_config;

    public $ERROR = NULL;
    public $ERROR_MESSAGE = '';

    /**
     * @param $map_alias
     * @param string $mode
     * param DBConnectionLite $dbi
     */
    public function __construct( $map_alias, $mode = 'file' /* , \DBConnectionLite $dbi */)
    {
        // $this->db_instance = $dbi;
        // $this->db_table_prefix = $dbi->get_table_prefix();

        $this->map_alias = $map_alias;
        $this->config_type = $mode;

        $this->template_file = 'viewmap.jslayout-struct.tpl';  // шаблон, куда вставляются данные из массива и генератор используется websun (но появляются пустые строки)
        $this->template_path = '$/templates/view.map';

        try {
            if ($this->map_alias == NULL)
                throw new \Exception("[JS Builder] Map alias not defined", 1);

            $this->json_config_filename = $json_config_filename = PATH_STORAGE . $this->map_alias . '/index.json';

            if (!is_file($json_config_filename))
                throw new \Exception("[JS Builder] {$json_config_filename} not found", 2);

            $this->json_config_content = file_get_contents( $json_config_filename );

            if (!$this->json_config_content)
                throw new \Exception("[JS Builer] Can't get content of {$json_config_filename} file.");

        } catch (\Exception $e) {
            $this->ERROR = TRUE;
            $this->ERROR_MESSAGE = $e->getMessage();
        }

        if ($this->ERROR) die($this->ERROR_MESSAGE);
    }

    public function loadConfig(){

        switch ($this->config_type) {
            case 'file' : {
                $this->loadConfig_File();
                break;
            }
            case 'mysql': {
                $this->loadConfig_MySQL();
                break;
            }
        }

        if ($this->ERROR) die($this->ERROR_MESSAGE);
    }

    private function loadConfig_File() {
        try {
            $json = json_decode( $this->json_config_content );

            if (!$json)
                throw new Exception("[JS Builder] {$this->json_config_filename} json file is invalid", 3);

            $this->json_config = $json;
        } catch (\Exception $e) {
            $this->ERROR = TRUE;
            $this->ERROR_MESSAGE = json_last_error_msg();
        }
    }

    private function loadConfig_MySQL() {

    }


    /**
     *
     */
    public function run()
    {
        /**
         * @var object $json
         */
        $json = $this->json_config;

        $image_info = array(
            'width'     =>  0,
            'height'    =>  0,
            'ox'        =>  0,
            'oy'        =>  0
        );
        $max_bounds = NULL;

        $regions_for_js = '';
        $paths_data = [];

        try {
            if ($json->type == "vector" && empty($json->image))
                throw new Exception("[JS Builder] Declared vectorized image-layer, but image definition not found.");

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

            $svg_filename = PATH_STORAGE . $this->map_alias . '/' . $json->layout->file;

            if (!is_file($svg_filename))
                throw new Exception("[JS Builder] Layout file {$svg_filename} not found.");

            $svg_content = file_get_contents( $svg_filename );

            if (strlen($svg_content) == 0)
                throw new Exception("[JS Builder] Layout file is empty");


            /* =============== Layout ============ */

            // информация о слоях
            if (empty($json->layout))
                throw new Exception("[JS Builder] Layout data not found.");

            // создаем инсанс парсера, передаем SVG-контент файла
            $sp = new SVGParser( $svg_content );

            if ($sp->svg_parsing_error)
                throw new Exception("[JS Builder] SVG Parsing error " . $sp->svg_parsing_error['message']);

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

                $sp->parseLayer($layer);   // парсит слой (определяет атрибут трансформации слоя и конвертит в объекты все элементы)

                // установим конфигурационные значения для пустых регионов для текущего слоя
                $sp->setLayerDefaultOptions($layer_config);

                // получаем все элементы на слое
                $paths_at_layer = $sp->getElementsAll();

                // теперь нам нужны айдишники этих элементов на слое. Их надо проверить в БД и заполнить значениями кастомных полей из БД
                $paths_at_layers_ids = implode(", ", array_map(function($item){
                    return "'{$item}'";
                }, array_keys($paths_at_layer)));

                // запросим БД на предмет кастомных значений и заполненности регионов
                $lm_engine = new LiveMapEngine( LMEConfig::get_dbi() );
                $paths_at_layer_filled = $lm_engine->getRegionsWithInfo( $this->map_alias, $paths_at_layers_ids);

                //@TODO: ЕСЛИ СЕКЦИЯ ОПИСАНИЯ СЛОЯ НЕ ОПРЕДЕЛЕНА - ДАННЫЕ ЗАГРУЖАЮТСЯ КАК-ТО СТРАННО

                foreach ($paths_at_layer_filled as $path) {
                    $id_region = $path['id_region'];

                    // обновляем $paths_at_layer [ $path["id_region"] ] значениями, полученными из базы. Если там нулл - то значениями из
                    // конфига - если там не нулл.

                    // БД > конфиг > nothing
                    // В ФУНКЦИЮ? НАДО ЛИ?

                    if ($layer_config->present->fill && $layer_config->present->fill == 1) {

                        if (!$path['fillColor'] && $layer_config->present->fillColor) {
                            $path['fillColor'] = $layer_config->present->fillColor;
                        }

                        if (!$path['fillOpacity'] && $layer_config->present->fillOpacity) {
                            $path['fillOpacity'] = $layer_config->present->fillOpacity;
                        }
                    }

                    if ($layer_config->present->stroke && $layer_config->present->stroke == 1) {

                        if (!$path['borderColor'] && $layer_config->present->borderColor) {
                            $path['borderColor'] = $layer_config->present->borderColor;
                        }

                        if (!$path['borderWidth'] && $layer_config->present->borderWidth) {
                            $path['borderWidth'] = $layer_config->present->borderWidth;
                        }

                        if (!$path['borderOpacity'] && $layer_config->present->borderOpacity) {
                            $path['borderOpacity'] = $layer_config->present->borderOpacity;
                        }
                    }

                    $path['title'] = htmlspecialchars($path['title'], ENT_QUOTES | ENT_HTML5);
                    unset($path['edit_date']);

                    $paths_at_layer[ $id_region ] = array_merge($paths_at_layer[ $id_region ], $path);
                }

                $paths_data += $paths_at_layer;
            }

            // maxbounds
            if (!empty($json->viewport->maxbounds)) {
                $max_bounds = [
                    'present'   =>  1,
                    'topleft_h'     =>  $json->viewport->maxbounds[0][0],
                    'topleft_w'     =>  $json->viewport->maxbounds[0][1],
                    'bottomright_h' =>  $json->viewport->maxbounds[1][0],
                    'bottomright_w' =>  $json->viewport->maxbounds[1][1]
                ];
            }

            // $regions_for_js = $sp->exportSPaths( $paths_data );

        } catch (\Exception $e) {

        }

        // теперь генерируем подстановочные значения для шаблона
        $this->template = new Template($this->template_file, $this->template_path);
        $this->template->set('/map', array(
            'title'         =>  $json->title,
            'alias'         =>  $this->map_alias,
            'imagefile'     =>  $json->image->file,
            'width'         =>  $image_info['width'],
            'height'        =>  $image_info['height'],
            'ox'            =>  $image_info['ox'],
            'oy'            =>  $image_info['oy'],
            'default_zoom'  =>  $json->viewport->zoom,
        ));
        $this->template->set('/defaults', array(
            'color'         =>  $json->regiondefaults->color,
            'width'         =>  $json->regiondefaults->width,
            'opacity'       =>  $json->regiondefaults->opacity,
            'fillcolor'     =>  $json->regiondefaults->fillcolor,
            'fillopacity'   =>  $json->regiondefaults->fillopacity
        ));
        $this->template->set('/viewport', array(
            'width'         =>  $json->viewport->width,
            'height'        =>  $json->viewport->height,
            'background_color'  =>  $json->viewport->background_color
        ));
        $this->template->set('/maxbounds', $max_bounds);

        $this->template->set('/regions', $paths_data);
    }

    /**
     * @return mixed
     */
    public function content()
    {
        if (method_exists($this->template, 'render'))
            return $this->template->render();
    }


}


 
