<?php
/**
 * User: Arris
 * Date: 16.01.2018, time: 9:22
 */
class JSLayoutBuilder extends UnitPrototype {
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
     */
    public function __construct( $map_alias, $mode = 'file' )
    {
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

            if (empty($json->layout))
                throw new Exception("[JS Builder] Layout data not found.");

            $svg_filename = PATH_STORAGE . $this->map_alias . '/' . $json->layout->file;

            if (!is_file($svg_filename))
                throw new Exception("[JS Builder] Layout file {$svg_filename} not found.");

            $svg_content = file_get_contents( $svg_filename );

            if (strlen($svg_content) == 0)
                throw new Exception("[JS Builder] Layout file is empty");

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

                foreach($json->layout->layers as $layer) {
                    $sp->parseLayer($layer);



                    $paths_data += $sp->getElementsAll();
                }

            } else {
                $sp->parseLayer("Paths");
                $paths_data += $sp->getElementsAll();
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

        // $this->template->set('/map/regions_list', $regions_for_js);

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


 
