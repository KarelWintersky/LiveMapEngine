<?php
/**
 * User: Arris
 * Date: 24.09.2017, time: 16:21
 */
class JSLayoutGenerator extends UnitPrototype {
    private $template;
    private $map_alias;
    private $map_source;

    /**
     * @param $map_alias
     * @param string $mode
     */
    public function __construct( $map_alias, $mode = 'file' )
    {
        $this->map_alias = $map_alias;
        $this->map_source = $mode;
    }



    /**
     *
     */
    public function run( $route = '' )
    {
        $is_correct = false;
        $image_data = array(
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
                $image_data = array(
                    'width'     =>  $json->image->width,
                    'height'    =>  $json->image->height,
                    'ox'        =>  $json->image->ox,
                    'oy'        =>  $json->image->oy
                );
            } else {
                if ($json->type == "vector") {
                    die('Declared vectorized image-layer, but image definition not found in file ' . $filename);
                } else {
                    $image_data = NULL;
                }
            }

            // имена слоёв мы тоже можем получить от пользователя


            if (!empty($json->files->layout)) {
                $svg_filename = PATH_STORAGE . $this->map_alias . '/' . $json->files->layout;
                $svg_content  = file_get_contents( $svg_filename );

                $s = new ParseSVG( $svg_content );

                $paths_data = array();

                if ($json->type === "vector") {

                    $s->parse("Paths", "");
                    $paths_data = $s->getPathsDefinition( $image_data );

                } elseif ($json->type === "bitmap") {

                    $s->parse("Paths", "Image");
                    $image_data = $s->getImageDefinition();
                    $paths_data = $s->getPathsDefinition( $image_data );

                }

                $regions_for_js = $s->exportSPaths( $paths_data );
            }
            else {
                // описание регионов не определено
                $regions_for_js = '';
            }

            // теперь генерируем подстановочные значения для шаблона
            $this->template = new Template('themap.js', '$/templates');
            $this->template->set('/map', array(
                'title'         =>  $json->title,
                'alias'         =>  $this->map_alias,
                'imagefile'     =>  $json->files->image,
                'width'         =>  $image_data['width'],
                'height'        =>  $image_data['height'],
                'ox'            =>  $image_data['ox'],
                'oy'            =>  $image_data['oy'],
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
                'height'        =>  $json->viewport->height
            ));
            $this->template->set('/map/regions_list', $regions_for_js);
        } else {
            // Если ничего не передано - возвращаем пустой файл
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
 
