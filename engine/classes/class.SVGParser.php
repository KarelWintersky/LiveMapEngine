<?php

/**
 * User: Arris
 * Date: 15.01.2018, time: 18:47
 */
class SVGParser {
    const VERSION                       = 2.1;
    const ROUND_PRECISION               = 4;
    /**
     * Constants for convert_SVGElement_to_Polygon()
     * see : https://www.w3.org/TR/SVG11/paths.html#InterfaceSVGPathSeg
     */
    const PATHSEG_UNDEFINED             = 0;
    const PATHSEG_REGULAR_KNOT          = 1;

    const PATHSEG_MOVETO_ABS            = 2;
    const PATHSEG_MOVETO_REL            = 3;
    const PATHSEG_CLOSEPATH             = 4;

    const PATHSEG_LINETO_HORIZONTAL_REL = 5;
    const PATHSEG_LINETO_HORIZONTAL_ABS = 6;

    const PATHSEG_LINETO_VERTICAL_REL   = 7;
    const PATHSEG_LINETO_VERTICAL_ABS   = 8;

    const PATHSEG_LINETO_REL            = 9;
    const PATHSEG_LINETO_ABS            = 10;

    const NAMESPACES = array(
        'svg'       =>  'http://www.w3.org/2000/svg',
        'xlink'     =>  'http://www.w3.org/1999/xlink',
        'inkscape'  =>  'http://www.inkscape.org/namespaces/inkscape',
        'sodipodi'  =>  'http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd',
        'rdf'       =>  'http://www.w3.org/1999/02/22-rdf-syntax-ns#'
    );

    private $svg;
    public  $svg_parsing_error = NULL;

    // слой/массив изображений
    // слой-контейнер с изображениями
    private $layer_images = [];

    // сдвиг слоя-контейнера с изображениями
    public $layer_images_translation = NULL;


    // сдвиг слоя-контейнера с изображениями
    private $layer_images_oxy = []; // useless?

    // данные трансляции из модели CSV XY в Screen CRS
    // = layer_paths_oxy
    public $crs_translation_options = NULL;

    // Имя текущего слоя-контейнера с данными
    private $layer_name = '';

    // Текущий слой-контейнер с данными.
    private $layer_elements = [];

    // Сдвиг (translate) элементов на текущем слое
    private $layer_elements_translation = NULL;

    // Конфиг текущего слоя
    /**
     * @var stdClass null
     */
    private $layer_elements_config = NULL;


    // ========================================================================================

    /**
     * Создает экземпляр класса
     * @param $svg_file_content
     */
    public function __construct( $svg_file_content ) {
        libxml_use_internal_errors(true);

        try {
            $this->svg = new \SimpleXMLElement( $svg_file_content );

            foreach (self::NAMESPACES as $ns => $definition) {
                $this->svg->registerXPathNamespace( $ns, $definition );
            }

        } catch (\Exception $e) {
            $this->svg_parsing_error = array(
                'state'     =>  TRUE,
                'code'      =>  $e->getCode(),
                'message'   =>  $e->getMessage()
            );
        }
    }

    /**
     * Парсит информацию об изображениях. Передается имя слоя (в противном случае изображения ищутся по всей SVG)
     * Изображения у нас обычно задают фон (изображение с индексом 0)
     *
     * @param $layer_name
     * @return bool|int
     */
    public function parseImages( $layer_name ) {


        if ($layer_name !== '') {
            $xpath_images_layer_attrs = '//svg:g[starts-with(@inkscape:label, "' . $layer_name . '")]';

            // @var SimpleXMLElement $images_layer_attrs
            $images_layer_attrs = $this->svg->xpath($xpath_images_layer_attrs);

            if ($images_layer_attrs) {
                $images_layer_attrs = $images_layer_attrs[0];
            } else {
                return FALSE;
            }

            if (empty($images_layer_attrs->attributes())) {
                return FALSE;
            }

            if (!empty($images_layer_attrs->attributes()->{'transform'})) {
                $this->layer_images_translation = $this->parseTransform( $images_layer_attrs->attributes()->{'transform'} );
            }

            $xpath_images   = '//svg:g[starts-with(@inkscape:label, "' . $layer_name . '")]/svg:image';

        } else {
            $xpath_images   = '//svg:image';
        }

        $this->layer_images = $this->svg->xpath($xpath_images);
        return TRUE;
    }

    /**
     * Возвращает количество изображений
     * @return int
     */
    public function getImagesCount() {
        return count($this->layer_images);
    }

    /**
     * Возвращает параметры сдвига при трансформации-переносе
     *
     * @param $transform_definition
     * @return array [ ox, oy ]
     */
    private function parseTransform($transform_definition) {

        if (1 == preg_match('/translate\(\s*([^\s,)]+)[\s,]([^\s,)]+)/', $transform_definition, $translate_matches)) {
            return [
                'ox'    =>  (float)$translate_matches[1],
                'oy'    =>  (float)$translate_matches[2]
            ];
        } else {
            return [
                'ox'    =>  0,
                'oy'    =>  0
            ];
        }
    }

    /**
     * Возвращает информацию об изображении с переданным индексом
     * @param $index
     * @return array|bool
     */
    public function getImageInfo($index = 0) {
        if (array_key_exists($index, $this->layer_images)) {

            /**
             * @var SimpleXMLElement $an_image
             */
            $an_image = $this->layer_images[ $index ];

            return array(
                'width'     =>  round((float)$an_image->attributes()->{'width'}, self::ROUND_PRECISION),
                'height'    =>  round((float)$an_image->attributes()->{'height'}, self::ROUND_PRECISION),
                'ox'        =>  round((float)$an_image->attributes()->{'x'} + (float)$this->layer_images_translation['ox'], self::ROUND_PRECISION),
                'oy'        =>  round((float)$an_image->attributes()->{'y'} + (float)$this->layer_images_translation['oy'], self::ROUND_PRECISION),
                'xhref'     =>       (string)$an_image->attributes('xlink', true)->{'href'}
            );
        } else {
            return FALSE;
        }
    }

    /**
     * Парсит объекты на определенном слое (или по всему файлу)
     * @param $layer_name
     * @return bool
     */
    public function parseLayer($layer_name) {
        if ($layer_name !== '') {
            $this->layer_name = $layer_name;

            // xpath атрибутов слоя разметки
            $xpath_paths_layer_attrs = '//svg:g[starts-with(@inkscape:label, "' . $layer_name . '")]';

            if (empty($this->svg->xpath($xpath_paths_layer_attrs))) return FALSE;

            $paths_layer_attrs = $this->svg->xpath($xpath_paths_layer_attrs)[0];

            // получаем сдвиг всех объектов этого слоя
            if (!empty($paths_layer_attrs->attributes()->{'transform'})) {
                $this->layer_elements_translation = $this->parseTransform( $paths_layer_attrs->attributes()->{'transform'} );
            } else {
                $this->layer_elements_translation = NULL;
            }

            $xpath_paths    = '//svg:g[starts-with(@inkscape:label, "' . $layer_name . '")]'; // все возможные объекты

            // + '/*' - список объектов (но без информации об объекте)
        } else {
            $xpath_paths    = '//svg:path'; //@todo: другое определение?
        }

        $this->layer_elements  = $this->svg->xpath($xpath_paths)[0];
    }

    /* ========================= */

    public function dump( $part = '' ) {
        $data = (array)$this->layer_elements;
        $data = $part != '' ? $data[$part] : $data ;

        var_dump( $data );
    }

    /**
     * Устанавливает опции трансляции данных слоя из модели CRS.XY в модель CRS.Simple
     *
     * Если не вызывали - трансляция не производится
     * @param $ox
     * @param $oy
     * @param $image_height
     */
    public function set_CRSSimple_TranslateOptions($ox = NULL , $oy = NULL, $image_height = NULL) {
        if (!( is_null($ox) || is_null($oy) || is_null($image_height))) {
            $this->crs_translation_options = [
                'ox'    =>  $ox,
                'oy'    =>  $oy,
                'height'=>  $image_height
            ];

        }
    }

    /**
     *
     *
     * @param SimpleXMLElement $element
     * @param string $type
     * @return array
     */
    public function parseAloneElement($element, $type) {
        $data = [];   // блок данных о пути

        $path_d     = (string)$element->attributes()->{'d'};
        $path_id    = (string)$element->attributes()->{'id'};
        $path_style = (string)$element->attributes()->{'style'};

        $data['id'] = $path_id;

        switch ($type) {
            case 'path' : {
                // кол-во путей ~
                // кол-во узлов ~
                $data['type'] = 'polygon';

                // SVG Path -> Polygon
                $coords = $this->convert_SVGElement_to_Polygon( $element );
                if (!$coords) return FALSE;

                // сдвиг координат и преобразрвание в CRS-модель
                $coords = $this->translate_polygon_from_XY_to_CRS( $coords );

                // convert_to_JS_style
                $data['coords'] = $coords;
                $data['js'] = $this->convert_CRS_to_JSString( $data['coords'] );

                break;
            }
            case 'circle': {
                // кол-во путей 1
                // кол-во узлов 1
                $data['type'] = 'circle';
                $data['radius'] = round((float)$element->attributes()->{'r'}, self::ROUND_PRECISION); //@todo: existance check

                // SVG Path -> Polygon
                $coords = $this->convert_SVGElement_to_Circle( $element );
                if (!$coords) return FALSE;

                // сдвиг координат и преобразрвание в CRS-модель
                $coords = $this->translate_knot_from_XY_to_CRS( $coords );

                $data['coords'] = $coords;
                $data['js'] = $this->convert_knotCRS_to_JSstring( $data['coords'] );

                break;
            }
            case 'rect': {
                // кол-во путей 1
                // кол-во узлов 2
                // x, y
                $data['type'] = 'rect';
                $coords = $this->convert_SVGElement_to_Rect( $element );
                if (!$coords) return FALSE;

                $data['coords'][] = [
                    $this->translate_knot_from_XY_to_CRS( $coords[0] ),
                    $this->translate_knot_from_XY_to_CRS( $coords[1] )
                ];

                $data['js'] = $this->convert_CRS_to_JSString($data['coords'] );

                break;
            }
            case 'ellipse': {
                $data['type'] = 'circle';

                //@todo: rx, ry existance check

                $data['radius'] = round( ( (float)$element->attributes()->{'rx'} + (float)$element->attributes()->{'ry'} ) /2 , self::ROUND_PRECISION);

                // SVG Element to coords
                $coords = $this->convert_SVGElement_to_Circle( $element );
                if (!$coords) return FALSE;

                // сдвиг координат и преобразрвание в CRS-модель
                $coords = $this->translate_knot_from_XY_to_CRS( $coords );

                $data['coords'] = $coords;
                $data['js'] = $this->convert_knotCRS_to_JSstring( $data['coords'] );

                break;
            }
        }

        // получем информацию об атрибутах региона из SVG-разметки

        // получаем атрибут fillColor
        if (preg_match('#fill:([\#\d\w]{7})#', $path_style, $path_style_fillColor) ) {
            $data['fillColor'] = $path_style_fillColor[1];
        } else {
            $data['fillColor'] = null;
        };

        // получаем атрибут fillOpacity
        if (preg_match('#fill-opacity:([\d]?\.[\d]{0,8})#', $path_style, $path_style_fillOpacity) ) {
            $data['fillOpacity'] = round($path_style_fillOpacity[1] , self::ROUND_PRECISION);
        } else {
            $data['fillOpacity'] = null;
        };

        // получаем атрибут fillRule
        if (preg_match('#fill-rule:(evenodd|nonzero)#', $path_style, $path_style_fillRule) ) {
            if ($path_style_fillRule[1] !== 'evenodd') {
                $data['fillRule'] = $path_style_fillRule[1];
            }
        } else {
            $data['fillRule'] = null;
        };

        // кастомные значения для пустых регионов
        if ($this->layer_elements_config) {
            if ($this->layer_elements_config->empty->fill && $this->layer_elements_config->empty->fill == 1) {

                if ($this->layer_elements_config->empty->fillColor && !$data['fillColor']) {
                    $data['fillColor'] = $this->layer_elements_config->empty->fillColor; //fillColor
                }

                if ($this->layer_elements_config->empty->fillOpacity && !$data['fillOpacity']) {
                    $data['fillOpacity'] = $this->layer_elements_config->empty->fillOpacity;
                }
            } // if ... $this->layer_elements_config->empty->fill == 1)

            if ($this->layer_elements_config->empty->stroke && $this->layer_elements_config->empty->stroke == 1) {

                if ($this->layer_elements_config->empty->borderColor && $data['borderColor']) {
                    $data['borderColor'] = $this->layer_elements_config->empty->borderColor;
                }

                if ($this->layer_elements_config->empty->borderWidth && $data['borderWidth']) {
                    $data['borderWidth'] = $this->layer_elements_config->empty->borderWidth;
                }

                if ($this->layer_elements_config->empty->borderOpacity && $data['borderOpacity']) {
                    $data['borderOpacity'] = $this->layer_elements_config->empty->borderOpacity;
                }

            } // if ... $this->layer_elements_config->empty->stroke == 1)

        } // if ($this->layer_elements_config)




        // получаем title узла
        $path_title = (string)$element->{'title'}[0];
        if ($path_title) {
            $data['title'] = htmlspecialchars($path_title, ENT_QUOTES | ENT_HTML5);
        }

        // получаем description узла
        $path_desc = (string)$element->{'desc'}[0];
        if ($path_desc) {
            $data['desc'] = htmlspecialchars($path_desc, ENT_QUOTES | ENT_HTML5);
        }

        $data['layer'] = $this->layer_name;

        return $data;
    }


    /**
     * Получаем элементы по типу (rect, circle, path)
     * @param $type
     * @return array
     */
    public function getElementsByType( $type ) {
        /** @var SimpleXMLElement $path */

        $all_paths = [];

        foreach ($this->layer_elements->{$type} as $path) {
            $path_id    = (string)$path->attributes()->{'id'};

            $all_paths[ $path_id ] = $this->parseAloneElement($path, $type);
        }

        return $all_paths;
    }

    /**
     * Получаем все элементы со слоя
     *
     * @return array
     */
    public function getElementsAll() {
        $all_paths = [];

        /** @var SimpleXMLElement $path */

        foreach ($this->layer_elements->{'path'} as $path) {
            $path_id    = (string)$path->attributes()->{'id'};
            $element    = $this->parseAloneElement($path, 'path');
            if ($element) $all_paths[ $path_id ] = $element;
        }

        foreach ($this->layer_elements->{'rect'} as $path) {
            $path_id    = (string)$path->attributes()->{'id'};
            $element    = $this->parseAloneElement($path, 'rect');
            if ($element) $all_paths[ $path_id ] = $element;
        }

        foreach ($this->layer_elements->{'circle'} as $path) {
            $path_id    = (string)$path->attributes()->{'id'};
            $element    = $this->parseAloneElement($path, 'circle');
            if ($element) $all_paths[ $path_id ] = $element;
        }

        foreach ($this->layer_elements->{'ellipse'} as $path) {
            $path_id    = (string)$path->attributes()->{'id'};
            $element    = $this->parseAloneElement($path, 'ellipse');
            if ($element) $all_paths[ $path_id ] = $element;
        }


        return $all_paths;
    }

    public function setLayerDefaultOptions($options) {
        $this->layer_elements_config = $options;
    }


    /**
     * Получаем все элементы типа PATH
     * @return array
     */
    public function getPaths() {
        return $this->getElementsByType('path');
    }


    /**
     * Получаем все элементы типа RECTANGLE
     * @return array
     */
    public function getRects() {
        return $this->getElementsByType('rect');
    }

    /**
     * Получаем все элементы типа CIRCLE
     * @return array
     */
    public function getCircles() {
        return $this->getElementsByType('circle');
    }

    /**
     * Получаем все элементы типа ELLIPSE
     * @return array
     */
    public function getEllipses() {
        return $this->getElementsByType('ellipse');
    }


    // ====================================================================================================

    // применяет трансформацию к узлу. Если не заданы опции трансформации - используются данные для трансформации слоя
    public function apply_transform_for_knot( $knot , $options = NULL) {

        if ($options === NULL) {
            $ox = $this->layer_elements_translation['ox'];
            $oy = $this->layer_elements_translation['oy'];
        } else {
            $ox = $options['ox'];
            $oy = $options['oy'];
        }

        return [
            'x' =>  $knot['x'] + $ox,
            'y' =>  $knot['y'] + $oy
        ];
    }

    // применяет трансформацию к субполигону
    public function apply_transform_for_subpolygon( $subpolyline, $options = NULL) {
        return array_map( function($knot) use ($options) {
            return $this->apply_transform_for_knot( $knot );
        }, $subpolyline);
    }

    // применяет трансформацию к мультиполигону
    public function apply_transform_for_polygon( $polygon, $options ) {
        if (empty($polygon)) return array();

        return
            ( count($polygon) > 1 )
            ?
                array_map( function($subpoly) use ($options) {
                    return $this->apply_transform_for_subpolygon($subpoly, $options);
                }, $polygon )
            :
                array(
                    $this->apply_transform_for_subpolygon( array_shift($polygon), $options)
                );
    }


    // convert CRS (SVG) to Simple
    public function convert_to_SimpleCRS_polygon( $polygon ) {
        if ( empty($polygon) ) return array();

        return
            ( count($polygon) > 1 )    // если суб-полигонов больше одного
                ?
                // проходим по всем
                array_map( function($subpath) {
                    return $this->convert_to_SimpleCRS_subpolygon( $subpath );
                }, $polygon )
                :
                // иначе возвращаем первый элемент массива субполигонов, но как единственный элемент массива!
                array(
                    $this->convert_to_SimpleCRS_subpolygon( array_shift($polygon) )
                );
    }

    public function convert_to_SimpleCRS_subpolygon( $subpolygon ) {
        return array_map( function($knot) {
            return $this->convert_to_SimpleCRS_knot( $knot );
        }, $subpolygon);
    }

    public function convert_to_SimpleCRS_knot( $knot ) {
        $ox = 0;
        $oy = 0;
        $height = 0; // height inversion

        // (X, Y) => (Height - (Y-oY) , (X-oX)
        return [
            'x'     =>  round( $height - ($knot['y'] - $oy), self::ROUND_PRECISION),
            'y'     =>  round(           ($knot['x'] - $ox), self::ROUND_PRECISION)
        ];
    }


    // ====================================================================================================

    /**
     * выполняет трансляцию узла в CRS-модель
     *
     * @todo: @warning: ГРЯЗНЫЙ ХАК: Тут мы сделали важное упрощение - сдвиг объектов на слое и трансляция данных в модель CRS делаются в одной функции, которая (если судить просто по имени) должна только транслировать вершину в CRS-модель. Это сделано для упрощения, но потенциально здесь может крыться ошибка!
     *
     * @param $knot
     * @return array
     */
    public function translate_knot_from_XY_to_CRS( $knot ) {
        $ox = 0;
        $oy = 0;
        $height = 0;

        if ($this->layer_elements_translation) {
            $ox += $this->layer_elements_translation['ox'];
            $oy += $this->layer_elements_translation['oy'];
        }

        if ($this->crs_translation_options) {
            $ox += $this->crs_translation_options['ox'];
            $oy += $this->crs_translation_options['oy'];
            $height = $this->crs_translation_options['height'];
        }

        // (X, Y) => (Height - (Y-oY) , (X-oX)
        return [
            'x'     =>  round( $height - ($knot['y'] - $oy) , self::ROUND_PRECISION),
            'y'     =>  round( $knot['x'] - $ox, self::ROUND_PRECISION)
        ];
    }

    /**
     * Преобразует субполигон из XY-модели в CRS-модель
     * @param $subpolyline
     * @return array
     */
    public function translate_subpolygon_from_XY_to_CRS( $subpolyline ) {
        return array_map( function($knot) {
            return $this->translate_knot_from_XY_to_CRS( $knot );
        }, $subpolyline);
    }

    // преобразует полигон в CRS-модель
    /**
     * Преобразует полигон
     * [0] => массив вершин (XY) (даже если полигон один и нет субполигонов)
     * [1] => массив вершин (XY)
     *
     * @param $polygone
     * @return array
     */
    public function translate_polygon_from_XY_to_CRS( $polygone ) {
        if ( empty($polygone) ) return array();

        return
            ( count($polygone) > 1 )    // если суб-полигонов больше одного
                ?                           // проходим по всем
                array_map( function($subpath) {
                    return $this->translate_subpolygon_from_XY_to_CRS( $subpath );
                }, $polygone )
                : // возвращаем первый элемент массива субполигонов, но как единственный элемент массива!
                array(
                    $this->translate_subpolygon_from_XY_to_CRS( array_shift($polygone)
                )
            );
    }

    /**
     * Преобразует элемент типа POLYGON в массив координат полигона.
     *
     * Возвращает массив пар координат ИЛИ false в случае невозможности преобразования.
     * Невозможно преобразовать кривые Безье любого вида. В таком случае возвращается пустой массив.
     *
     * Эта функция не выполняет сдвиг или преобразование координат! У неё нет для этого данных.
     *
     * @param SimpleXMLElement $element
     * @return array
     */
    public function convert_SVGElement_to_Polygon( $element ) {

        // @var SimpleXMLElement $element
        // получаем значение атрибута <path d="">
        $path     = (string)$element->attributes()->{'d'};

        $xy = array();
        $is_debug = false;

        // пуст ли путь?
        if ($path === '') return array();

        // если путь не заканчивается на z/Z - это какая-то херня, а не путь. Отбрасываем
        //@todo: [УЛУЧШИТЬ] PARSE_SVG -- unfinished paths may be correct?
        if ( 'z' !== strtolower(substr($path, -1)) ) {
            return array();
        }

        // выясняем наличие атрибута transform:translate (другие варианты трансформации не обрабатываются)
        $translate = [
            'x' =>  0,
            'y' =>  0
        ];
        $transform = (string)$element->attributes()->{'transform'};

        $translate = $this->parseTransform($transform);
        //@todo: добавить обработку трансформации элемента



        // есть ли в пути управляющие последовательности кривых Безье любых видов?
        $charlist_unsupported_knots = 'CcSsQqTtAa'; // так быстрее, чем регулярка по '#(C|c|S|s|Q|q|T|t|A|a)#'
        if (strpbrk($path, $charlist_unsupported_knots)) {
            return array();
        }

        $path_fragments = explode(' ', $path);

        $polygon = array();             // массив узлов полигона
        $multipolygon = array();        // массив, содержащий все полигоны. Если в нём один элемент - то у фигуры один полигон.

        $polygon_is_relative = null;    // тип координат: TRUE - Относительные, FALSE - абсолютные, null - не определено
        $prev_knot_x = 0;               // X-координата предыдущего узла
        $prev_knot_y = 0;               // Y-координата предыдущего узла

        $path_start_x = 0;              // X-координата начала текущего пути
        $path_start_y = 0;              // Y-координата начала текущего пути

        $LOOKAHEAD_FLAG = self::PATHSEG_UNDEFINED;

        do {
            $fragment = array_splice($path_fragments, 0, 1)[0];

            if ($is_debug) echo PHP_EOL, "Извлеченный фрагмент : ", $fragment, PHP_EOL;

            if ( $fragment === 'Z') $fragment = 'z';

            if ( strpbrk($fragment, 'MmZzHhVvLl') ) {    // faster than if (preg_match('/(M|m|Z|z|H|h|V|v|L|l)/', $fragment) > 0)
                switch ($fragment) {
                    case 'M' : {
                        $LOOKAHEAD_FLAG = self::PATHSEG_MOVETO_ABS;
                        break;
                    }
                    case 'm' : {
                        $LOOKAHEAD_FLAG = self::PATHSEG_MOVETO_REL;
                        break;
                    }
                    case 'z': {
                        $LOOKAHEAD_FLAG = self::PATHSEG_CLOSEPATH;
                        break;
                    }
                    case 'h': {
                        $LOOKAHEAD_FLAG = self::PATHSEG_LINETO_HORIZONTAL_REL;
                        break;
                    }
                    case 'H': {
                        $LOOKAHEAD_FLAG = self::PATHSEG_LINETO_HORIZONTAL_ABS;
                        break;
                    }
                    case 'v': {
                        $LOOKAHEAD_FLAG = self::PATHSEG_LINETO_VERTICAL_REL;
                        break;
                    }
                    case 'V': {
                        $LOOKAHEAD_FLAG = self::PATHSEG_LINETO_VERTICAL_ABS;
                        break;
                    }
                    case 'l': {
                        $LOOKAHEAD_FLAG = self::PATHSEG_LINETO_REL;
                        break;
                    }
                    case 'L': {
                        $LOOKAHEAD_FLAG = self::PATHSEG_LINETO_ABS;
                        break;
                    }
                } // switch

                // обработка управляющей последовательности Z
                if ($LOOKAHEAD_FLAG === self::PATHSEG_CLOSEPATH) {
                    $multipolygon[] = $polygon; // добавляем суб-полигон к полигону
                    $polygon = array();         // очищаем массив узлов суб-полигона
                }

                if ($is_debug) echo "Это управляющая последовательность. Параметры будут обработаны на следующей итерации.", PHP_EOL, PHP_EOL;
                continue;
            } else {
                if ($is_debug) echo "Это числовая последовательность, запускаем обработчик : ";

                /**
                 * Раньше этот блок данных обрабатывался внутри назначения обработчиков.
                 * Сейчас я его вынес наружу. Это может вызвать в перспективе некоторые глюки, нужны тесты
                 */
                if ($LOOKAHEAD_FLAG == self::PATHSEG_MOVETO_REL) {
                    if ($is_debug) echo "m : Начало полилинии с относительными координатами ", PHP_EOL;
                    $polygon_is_relative = true;

                    //@todo: Подумать над ускорением преобразования (ЧИСЛО,ЧИСЛО)

                    $pattern = '#(?<X>\-?\d+(\.\d+)?)\,(?<Y>\-?\d+(\.\d+)?)+#';
                    $matches_count = preg_match($pattern, $fragment, $knot);

                    // так как путь относительный, moveto делается относительно предыдущего положения "пера"
                    // вообще, скорее всего, нам не нужны совсем переменные $path_start_x и $path_start_y
                    $path_start_x = $prev_knot_x;
                    $path_start_y = $prev_knot_y;

                    if ($matches_count > 0) {
                        $xy = array(
                            'x' =>  $path_start_x + $knot['X'],
                            'y' =>  $path_start_y + $knot['Y']
                        );
                        $polygon[] = $xy;

                        $prev_knot_x = $xy['x'];
                        $prev_knot_y = $xy['y'];

                        $path_start_x = $prev_knot_x;
                        $path_start_y = $prev_knot_y;
                    }

                    $LOOKAHEAD_FLAG = self::PATHSEG_UNDEFINED;
                    if ($is_debug) var_dump($xy);
                    continue; // ОБЯЗАТЕЛЬНО делаем continue, иначе управление получит следующий блок
                }

                if ($LOOKAHEAD_FLAG == self::PATHSEG_MOVETO_ABS) {
                    if ($is_debug) echo "M : Начало полилинии с абсолютными координатами ", PHP_EOL;
                    $polygon_is_relative = false;

                    //@todo: Подумать над ускорением преобразования (ЧИСЛО,ЧИСЛО)
                    $pattern = '#(?<X>\-?\d+(\.\d+)?)\,(?<Y>\-?\d+(\.\d+)?)+#';
                    $matches_count = preg_match($pattern, $fragment, $knot);

                    // вообще, скорее всего, нам не нужны совсем переменные $path_start_x и $path_start_y
                    $path_start_x = 0;
                    $path_start_y = 0;

                    if ($matches_count > 0) {
                        $xy = array(
                            'x' =>  $path_start_x + $knot['X'],
                            'y' =>  $path_start_y + $knot['Y']
                        );
                        $polygon[] = $xy;

                        $prev_knot_x = 0;
                        $prev_knot_y = 0;
                    }

                    $LOOKAHEAD_FLAG = self::PATHSEG_UNDEFINED;

                    if ($is_debug) var_dump($xy);

                    continue; // ОБЯЗАТЕЛЬНО делаем continue, иначе управление получит следующий блок
                }

                if ($LOOKAHEAD_FLAG == self::PATHSEG_UNDEFINED || $LOOKAHEAD_FLAG == self::PATHSEG_REGULAR_KNOT ) {
                    if ($is_debug) echo "Обычная числовая последовательность ", PHP_EOL;

                    // проверяем валидность пары координат
                    //@todo: Подумать над ускорением проверки (ЧИСЛО,ЧИСЛО)
                    //@todo: формат с запятыми - это inkscape-friendly запись. Стандарт считает, что запятая не нужна и числа идут просто парами через пробел.

                    $pattern = '#(?<X>\-?\d+(\.\d+)?)\,(?<Y>\-?\d+(\.\d+)?)+#';
                    $matches_count = preg_match($pattern, $fragment, $knot);

                    // Если это неправильная комбинация float-чисел - пропускаем обработку и идем на след. итерацию
                    if ($matches_count == 0) continue;
                    // здесь я использую такую конструкцию чтобы не брать стену кода в IfTE-блок.

                    if (empty($polygon)) {
                        // возможно обработку первого узла следует перенести в другой блок (обработчик флага SVGPATH_START_ABSOULUTE или SVGPATH_START_RELATIVE)
                        // var_dump('Это первый узел. Он всегда задается в абсолютных координатах! ');

                        $xy = array(
                            'x' =>  $prev_knot_x + $knot['X'],
                            'y' =>  $prev_knot_y + $knot['Y']
                        );

                        $polygon[] = $xy;

                        $prev_knot_x = $xy['x'];
                        $prev_knot_y = $xy['y'];
                    } else {
                        // var_dump('Это не первый узел в мультилинии');

                        if ($polygon_is_relative) {
                            // var_dump("его координаты относительные и даны относительно предыдущего узла полилинии ");

                            $xy = array(
                                'x' =>  $prev_knot_x + $knot['X'],
                                'y' =>  $prev_knot_y + $knot['Y']
                            );

                            $polygon[] = $xy;

                            $prev_knot_x = $xy['x'];
                            $prev_knot_y = $xy['y'];

                        } else {
                            // var_dump("Его координаты абсолютные");

                            $xy = array(
                                'x' =>  $knot['X'],
                                'y' =>  $knot['Y']
                            );

                            $polygon[] = $xy;

                            // "предыдущие" координаты все равно надо хранить.
                            $prev_knot_x = $xy['x'];
                            $prev_knot_y = $xy['y'];

                        } // if()
                    } // endif (polygon)
                    if ($is_debug) var_dump($xy);
                    unset($xy);
                } // if ($LOOKAHEAD_FLAG == SVGPATH_UNDEFINED || $LOOKAHEAD_FLAG == SVGPATH_NORMAL_KNOT )

                if ($LOOKAHEAD_FLAG == self::PATHSEG_LINETO_HORIZONTAL_ABS) {
                    if ($is_debug) echo "Горизональная линия по абсолютным координатам ", PHP_EOL;

                    //@todo: по стандарту,
                    /**
                     * Использование вертикальных и горизонтальных линий с абсолютными координатами заметно увеличивает код.
                     * задается ДВА числа
                     *
                     * <path d="M 110 210 H 50 210"  stroke-width="3" stroke="red"/
                     *
                     * При использовании относительных координат код становится заметно короче, так как задается только длина
                     * (h) от последней точки пути для горизонтальных линий и высота (v) для вертикальных линий.
                     *
                     */

                    $LOOKAHEAD_FLAG = self::PATHSEG_UNDEFINED;

                    //@todo: Подумать над ускорением проверки (ЧИСЛО)
                    $pattern = '#(?<X>\-?\d+(\.\d+)?)#';
                    $matches_count = preg_match($pattern, $fragment, $knot);

                    if ($matches_count > 0) {
                        $xy = array(
                            'x' =>  $knot['X'],
                            'y' =>  $prev_knot_y
                        );

                        $polygon[] = $xy;

                        $prev_knot_x = $xy['x'];
                        $prev_knot_y = $xy['y'];
                    }
                }

                if ($LOOKAHEAD_FLAG == self::PATHSEG_LINETO_HORIZONTAL_REL) {
                    if ($is_debug) echo "Горизональная линия по относительным координатам ", PHP_EOL;
                    $LOOKAHEAD_FLAG = self::PATHSEG_UNDEFINED;

                    //@todo: Подумать над ускорением проверки (ЧИСЛО)
                    $pattern = '#(?<X>\-?\d+(\.\d+)?)#';
                    $matches_count = preg_match($pattern, $fragment, $knot);

                    if ($matches_count > 0) {
                        $xy = array(
                            'x' =>  $prev_knot_x + $knot['X'],
                            'y' =>  $prev_knot_y
                        );

                        $polygon[] = $xy;

                        $prev_knot_x = $xy['x'];
                        $prev_knot_y = $xy['y'];
                    }
                } // ($LOOKAHEAD_FLAG == SVGPATH_HORIZONTAL_RELATIVE)

                if ($LOOKAHEAD_FLAG == self::PATHSEG_LINETO_VERTICAL_ABS) {
                    if ($is_debug) echo "Вертикальная линия по абсолютным координатам ", PHP_EOL;
                    $LOOKAHEAD_FLAG = self::PATHSEG_UNDEFINED;

                    /**
                     * Использование вертикальных и горизонтальных линий с абсолютными координатами заметно увеличивает код.
                     * задается ДВА числа
                     *
                     * <path d="M 110 210 V 110 10"  stroke-width="3" stroke="red"/
                     *
                     * При использовании относительных координат код становится заметно короче, так как задается только длина
                     * (h) от последней точки пути для горизонтальных линий и высота (v) для вертикальных линий.
                     *
                     */

                    //@todo: Подумать над ускорением проверки (ЧИСЛО)
                    $pattern = '#(?<Y>\-?\d+(\.\d+)?)#';
                    $matches_count = preg_match($pattern, $fragment, $knot);

                    if ($matches_count > 0) {
                        $xy = array(
                            'x' =>  $prev_knot_x,
                            'y' =>  $knot['Y']
                        );

                        $polygon[] = $xy;

                        $prev_knot_x = $xy['x'];
                        $prev_knot_y = $xy['y'];
                    }
                } // ($LOOKAHEAD_FLAG == SVGPATH_VERTICAL_ABSOLUTE)

                if ($LOOKAHEAD_FLAG == self::PATHSEG_LINETO_VERTICAL_REL) {
                    if ($is_debug) echo "Вертикальная линия по относительным координатам ", PHP_EOL;
                    $LOOKAHEAD_FLAG = self::PATHSEG_UNDEFINED;

                    //@todo: Подумать над ускорением проверки (ЧИСЛО)
                    $pattern = '#(?<Y>\-?\d+(\.\d+)?)#';
                    $matches_count = preg_match($pattern, $fragment, $knot);

                    if ($matches_count > 0) {
                        $xy = array(
                            'x' =>  $prev_knot_x,
                            'y' =>  $prev_knot_y + $knot['Y']
                        );

                        $polygon[] = $xy;

                        $prev_knot_x = $xy['x'];
                        $prev_knot_y = $xy['y'];
                    }


                } // ($LOOKAHEAD_FLAG == SVGPATH_VERTICAL_RELATIVE)

                if ($LOOKAHEAD_FLAG == self::PATHSEG_LINETO_ABS) {
                    if ($is_debug) echo "Линия по абсолютным координатам ", PHP_EOL;

                    //@todo: Подумать над ускорением проверки (ЧИСЛО)
                    $pattern = '#(?<X>\-?\d+(\.\d+)?)\,(?<Y>\-?\d+(\.\d+)?)+#';
                    $matches_count = preg_match($pattern, $fragment, $knot);

                    if ($matches_count > 0) {
                        $xy = array(
                            'x' =>  $knot['X'],
                            'y' =>  $knot['Y']
                        );

                        $polygon[] = $xy;

                        $prev_knot_x = $xy['x'];
                        $prev_knot_y = $xy['y'];
                    }

                } // ($LOOKAHEAD_FLAG == SVGPATH_LINETO_ABSOLUTE)

                if ($LOOKAHEAD_FLAG == self::PATHSEG_LINETO_REL) {
                    if ($is_debug) echo "Линия по относительным координатам ", PHP_EOL;

                    //@todo: Подумать над ускорением проверки (ЧИСЛО)
                    $pattern = '#(?<X>\-?\d+(\.\d+)?)\,(?<Y>\-?\d+(\.\d+)?)+#';
                    $matches_count = preg_match($pattern, $fragment, $knot);

                    if ($matches_count > 0) {
                        $xy = array(
                            'x' =>  $prev_knot_x + $knot['X'],
                            'y' =>  $prev_knot_y + $knot['Y']
                        );

                        $polygon[] = $xy;

                        $prev_knot_x = $xy['x'];
                        $prev_knot_y = $xy['y'];
                    }
                } // ($LOOKAHEAD_FLAG == SVGPATH_LINETO_ABSOLUTE)

                if ($is_debug && isset($xy)) var_dump($xy);
            } // endif (нет, это не управляющая последовательность)

        } while (!empty($path_fragments));

        // обработка мультиполигона

        if ($is_debug) var_dump($multipolygon);

        return $multipolygon;
    }

    /**
     * @param SimpleXMLElement $element
     * @return array
     */
    public function convert_SVGElement_to_Circle( $element ) {
        return [
            'x' =>  (string)$element->attributes()->{'cx'},
            'y' =>  (string)$element->attributes()->{'cy'}
        ];
    }

    /**
     * @param SimpleXMLElement $element
     * @return array
     */
    private function convert_SVGElement_to_Rect($element)
    {
        $x = $element->attributes()->{'x'};
        $y = $element->attributes()->{'y'};
        $w = $element->attributes()->{'width'};
        $h = $element->attributes()->{'height'};

        return [
            [ 'x' => 0 + $x, 'y' => 0 + $y ],
            [ 'x' => $x + $w, 'y' => $y + $h ]
        ];

    }


    /* ======================    CONVERSION ================== */

    /**
     * Преобразует массив с данными мультиполигона в JS-строку ( [ [ [][] ], [ [][][] ] ])
     * @param $multicoords
     * @return array|string
     */
    public function convert_CRS_to_JSString( $multicoords )
    {
        if (empty($multicoords)) return '[]';

        $js_coords_string = array();

        if (count($multicoords) > 1) {
            array_walk( $multicoords, function($sub_coords) use (&$js_coords_string) {
                $js_coords_string[] = $this->convert_subCRS_to_JSstring( $sub_coords );
            });
            return '[ ' . implode(', ' , $js_coords_string) . ' ]';
        }
        else
        {
            $js_coords_string = $this->convert_subCRS_to_JSstring( array_shift($multicoords));
            return $js_coords_string;
        }

    }

    /**
     * Преобразует информацию об узле в JS-строку
     * @param $knot
     * @return string
     */
    public function convert_knotCRS_to_JSstring ( $knot ) {
        return '[' . implode(',', array(
            $knot['x'],
            $knot['y']
        )) . ']';
    }

    /**
     * Преобразует информацию о субполигоне (одиночном полигоне) в JS-строку
     * @param $coords
     * @return string
     */
    public function convert_subCRS_to_JSstring( $coords )
    {
        $js_coords_string = array();

        array_walk( $coords, function($knot) use (&$js_coords_string) {
            $js_coords_string[] = $this->convert_knotCRS_to_JSstring( $knot );
        });

        return '[ ' . implode(', ' , $js_coords_string) . ' ]';
    }

    /* =================== EXPORT ==================== */

    /**
     * DEPRECATED
     * Подготавливает данные для экспорта в шаблон
     *
     * @param $all_paths
     * @return string
     */
    public function exportSPaths( $all_paths ) {
        $all_paths_text = array();

        foreach($all_paths as $path_id => $path_data ) {
            $coords_js = $path_data['js'];

$path_data_text = <<<PDT
        '{$path_id}': {
PDT;
            $path_data_text .= "
            'id': '{$path_id}',
            'type': '{$path_data['type']}',
            'coords': {$coords_js}";

            if (array_key_exists('fillColor', $path_data)) {
                $path_data_text .= ', ' . PHP_EOL . "            'fillColor' : '{$path_data['fillColor']}'";
            }
            if (array_key_exists('fillOpacity', $path_data)) {
                $path_data_text .= ', ' . PHP_EOL . "            'fillOpacity' : '{$path_data['fillOpacity']}'";
            }
            if (array_key_exists('fillRule', $path_data)) {
                $path_data_text .= ', ' . PHP_EOL . "            'fillRule' : '{$path_data['fillRule']}'";
            }
            if (array_key_exists('title', $path_data)) {
                $path_data_text .= ', ' . PHP_EOL . "            'title' : '{$path_data['title']}'";
            }
            if (array_key_exists('desc', $path_data)) {
                $path_data_text .= ', ' . PHP_EOL . "            'desc' : '{$path_data['desc']}'";
            }

            if (array_key_exists('radius', $path_data)) {
                $path_data_text .= ', ' . PHP_EOL . "            'radius' : '{$path_data['radius']}'";
            }

            $path_data_text .= PHP_EOL.'        }';

            $all_paths_text[] = $path_data_text;
        }

        // массив строк оборачиваем запятой если нужно
        $all_path_as_text = implode(','.PHP_EOL, $all_paths_text);
        return $all_path_as_text;
    }


}
