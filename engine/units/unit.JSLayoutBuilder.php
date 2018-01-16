<?php
/**
 * User: Arris
 * Date: 16.01.2018, time: 9:22
 */
class JSLayoutBuilder extends UnitPrototype {
    private $template;
    private $map_alias;
    private $map_source;

    private $template_file = '';
    private $template_path = '';

    /**
     * @param $map_alias
     * @param string $mode
     */
    public function __construct( $map_alias, $mode = 'file' )
    {
        $this->map_alias = $map_alias;
        $this->map_source = $mode;

        $this->template_file = 'viewmap.jslayout.js';
        $this->template_path = '$/templates';
    }

    /**
     *
     */
    public function run()
    {
        $image_info = array(
            'width'     =>  0,
            'height'    =>  0,
            'ox'        =>  0,
            'oy'        =>  0
        );

        // проверяем второй параметр роутинга - это имя карты.
        if ($this->map_alias != NULL) {
            $filename = PATH_STORAGE . $this->map_alias . '/index.json';

            if (!is_file($filename)) {
                die('Incorrect path: ' . PATH_STORAGE . $this->map_alias);
            }

            $json = json_decode( file_get_contents( $filename ) );

            if (!empty($json->image)) {
                $image_info = array(
                    'width'     =>  $json->image->width,
                    'height'    =>  $json->image->height,
                    'ox'        =>  $json->image->ox,
                    'oy'        =>  $json->image->oy
                );
            } else {
                if ($json->type == "vector") {
                    die('Declared vectorized image-layer, but image definition not found in file ' . $filename);
                } else {
                    $image_info = NULL;
                }
            }

            // имена слоёв мы тоже можем получить от пользователя
            if (!empty($json->layout)) {
                $svg_filename = PATH_STORAGE . $this->map_alias . '/' . $json->layout->file;

                $svg_content  = file_get_contents( $svg_filename );

                // создаем инсанс парсера, передаем SVG-контент файла
                $sp = new SVGParser( $svg_content );

                $layer_name = "Image";
                $sp->parseImages( $layer_name );

                if ($json->type === "bitmap" && $sp->getImagesCount()) {
                    $image_info = $sp->getImageInfo();
                    $sp->setTranslateOptions( $image_info['ox'], $image_info['oy'], $image_info['height'] );
                } else {
                    $sp->setTranslateOptions( 0, 0, $image_info['height'] );
                }

                $sp->parseLayer("Paths");

                $paths_data = $sp->getElementsAll();

                $regions_for_js = $sp->exportSPaths( $paths_data );
            };


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
            $this->template->set('/map/regions_list', $regions_for_js);
        } else {
        }
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


 
