/**
 * Попытка запихнуть все методы работы с картой в класс
 */
class MapManager {
    /**
     * Инстанс инфобокса, null - когда не создан
     */
    static __InfoBox = null;

    /**
     * Текущий регион, для которого открыт инфобокс
     * @type {null}
     */
    static current_infobox_region_id = null;

    /**
     *
     * @type {{}}
     */
    regionsDataset = {};

    /**
     *
     * @param mapDefinition - определение карты, полученное из JS-запроса `/map:js/ID.js`
     * @param options
     * @param is_debug
     */
    constructor(mapDefinition = {}, options = {}, is_debug = false)
    {
        this.options = {
            use_canvas: true
        }

        jQuery.extend(this.options, options);

        this.theMap = mapDefinition;
        this.IS_DEBUG = is_debug;
    }

    /**
     * Устанавливает фон для контейнера карты
     *
     * @param target
     */
    setBackgroundColor(target) {
        $(target).css('background-color', this.theMap['display']['background_color']);
    }

    /**
     * Создает карту и зум на ней, в зависимости от параметров
     *
     * @param target
     * @returns {null}
     */
    createMap(target) {
        let map = null;

        let use_zoom_slider;
        let use_zoom_slider_position = this.theMap['display']['zoom_slider_position'] || 'bottomright';

        let _options = {
            crs: L.CRS.Simple,
            minZoom: this.theMap['display']['zoom_min'],
            maxZoom: this.theMap['display']['zoom_max'],
            preferCanvas: false,
            renderer: L.svg({ padding: Number(this.theMap['display']['zoom_max']) + 1 }), // должно быть, походу, maxzoom+1
        };

        if (this.options.use_canvas) {
            _options.preferCanvas = true;
            _options.renderer = L.canvas();
        } else {
            _options.preferCanvas = false;
            _options.renderer = L.svg({ padding: 3 }); // должно быть, походу, maxzoom+1
        }

        switch (this.theMap['display']['zoom_mode']) {
            case 'native': {
                _options.zoomControl = true;
                use_zoom_slider = false;
                break;
            }
            case 'smooth': {
                _options.scrollWheelZoom = false;   // disable original zoom function
                _options.smoothWheelZoom = true;    // enable smooth zoom
                _options.smoothSensitivity = 1;     // zoom speed. default is 1
                use_zoom_slider = false;
                _options.zoomControl = true;
                break;
            }
            default: {
                _options.zoomControl = false;
                use_zoom_slider = true;
            }
        }

        switch (this.theMap.map.type) {
            case 'bitmap': {
                map = L.map(target, _options);

                if (use_zoom_slider) {
                    map.addControl(new L.Control.Zoomslider({position: use_zoom_slider_position}));
                }

                break;
            }
            case 'vector': {
                map = L.map(target, _options);

                if (use_zoom_slider) {
                    map.addControl(new L.Control.Zoomslider({position: use_zoom_slider_position}));
                }

                break;
            }
            case 'tileset': {
                //@todo
                break;
            }
        }

        return map;
    } // -createMap

    /**
     * Возвращает bounds карты. И вроде бы не используется для именно setBounds()
     *
     * @returns {(number[]|*[])[]}
     */
    getBounds()
    {
        let bounds = [
            [0, 0],
            [this.theMap['map']['height'], this.theMap['map']['width']]
        ];

        /*if (theMap['display']['maxbounds']) {
        let mb = theMap['display']['maxbounds'];

        bounds = [
            [
                // mb['topleft_h'] * theMap['map']['height'],
                // mb['topleft_w'] * theMap['map']['width']
                0, 0
            ],
            [
                mb['bottomright_h'] * theMap['map']['height'],
                mb['bottomright_w'] * theMap['map']['width']
            ]
        ];
        }*/
        return bounds;
    }

    createImageOverlay(base_map_bounds) {
        let image = null;

        switch (this.theMap.map.type) {
            case 'bitmap': {
                image = L.imageOverlay( this.theMap['map']['imagefile'], base_map_bounds);
                break;
            }
            case 'vector': {
                image = L.imageOverlay( this.theMap['map']['imagefile'], base_map_bounds);
                break;
            }
            case 'tileset': {
                //@todo: почему ESO-то?
                // storage/ID/tiles/z/x_y.jpg - наверное так должно быть?
                L.tileLayer('eso/{z}/{x}/{y}.jpg', {
                    minZoom: theMap['display']['zoom_min'],
                    maxZoom: theMap['display']['zoom_max'],
                    attribution: 'ESO/INAF-VST/OmegaCAM',
                    tms: true
                });

                break;
            }
        }

        return image;
    }

    /**
     * Строит датасет регионов на карте с информацией о стилях отображения
     * Бывший buildPolymap()
     * @todo: Реализовать в folio и colorbox режимах
     *
     * @returns {null}
     */
    buildRegionsDataset() {
        let theMapRegions = this.theMap['regions'];
        let defaultDisplaySettings = this.theMap.display;

        let dataset = Object.create(null);

        Object.keys( theMapRegions ).forEach(function( key ) {
            let region = theMapRegions[key];

            let type = region['type'];
            let coords = region['coords'];
            let layer = region['layer'];

            let options = Object.create(null);
            options = {
                id: region.id,
                title: region.title || region.id,
                coords: coords,
                layer: layer,
                radius: region['radius'] || 10,

                // present или empty - нужно брать из данных о регионе (пока что берётся present для всех регионов).
                /* Параметры по-умолчанию для создания региона. В дальнейшем (on('mouseout'), on('mouseover') будем брать из структуры region */
                /* Это изменяемые параметры для региона. Они будут использованы для его создания */
                stroke: region['stroke'] || defaultDisplaySettings.region.present.stroke,
                color: region['borderColor'] || defaultDisplaySettings.region.present.borderColor,
                width: region['borderWidth'] || defaultDisplaySettings.region.present.borderWidth,
                opacity: region['borderOpacity'] || defaultDisplaySettings.region.present.borderOpacity,
                fill: region['fill'] || defaultDisplaySettings.region.present.fill,
                fillColor: region['fillColor'] || defaultDisplaySettings.region.present.fillColor,
                fillOpacity: region['fillOpacity'] || defaultDisplaySettings.region.present.fillOpacity,
                display_defaults: {},
            };

            /*
            А это неизменяемые параметры, они будут использованы для изменения стилей при событиях
            on('mouseover') и on('mouseout')
            * */
            options.display_defaults = {
                region: {
                    default: {
                        stroke: options['stroke'],
                        borderColor: options['color'],
                        borderWidth: options['width'],
                        borderOpacity: options['borderOpacity'],
                        fill: options['fill'],
                        fillColor: options['fillColor'],
                        fillOpacity: options['fillOpacity'],
                    },
                    hover: {
                        stroke: defaultDisplaySettings.region.present_hover.stroke,
                        borderColor: defaultDisplaySettings.region.present_hover.borderColor,
                        borderWidth: defaultDisplaySettings.region.present_hover.borderWidth,
                        borderOpacity: defaultDisplaySettings.region.present_hover.borderOpacity,
                        fill: defaultDisplaySettings.region.present_hover.fill,
                        fillColor: defaultDisplaySettings.region.present_hover.fillColor,
                        fillOpacity: defaultDisplaySettings.region.present_hover.fillOpacity,
                    }
                },
                poi: {
                    any: {
                        iconClass: defaultDisplaySettings.poi.any.iconClass,
                        markerColor: defaultDisplaySettings.poi.any.markerColor,
                        iconColor: defaultDisplaySettings.poi.any.iconColor,
                        iconXOffset: defaultDisplaySettings.poi.any.iconXOffset,
                        iconYOffset: defaultDisplaySettings.poi.any.iconYOffset,
                    },
                    /*default: {
                        iconClasses: 'fa-brands fa-fort-awesome', // display.poi.any
                        markerColor: 'green',
                        iconColor: '#FFF',
                        iconXOffset: -1,
                        iconYOffset: 0
                    },
                    hover: {
                        iconClasses: 'fa-brands fa-fort-awesome', // display.poi.any
                        markerColor: 'red',
                        iconColor: '#FFF',
                        iconXOffset: -1,
                        iconYOffset: 0
                    }*/
                }
            };

            let entity = null;
            switch (type) {
                case 'polygon': {
                    options.type = 'polygon';
                    entity = L.polygon(coords, options);
                    break;
                }
                case 'rect': {
                    options.type = 'rect';
                    entity = L.rectangle(coords, options);
                    break;
                }
                case 'circle': {
                    options.type = 'circle';
                    entity = L.circle(coords, options);
                    break;
                }
                case 'marker': {
                    options.type = 'poi';
                    options.keyboard = false;

                    let fa = {
                        icon: `fa ${options.display_defaults.poi.any.iconClass}`,
                        markerColor: options.display_defaults.poi.any.markerColor,
                        iconColor: options.display_defaults.poi.any.iconColor,
                        iconXOffset: options.display_defaults.poi.any.iconXOffset,
                        iconYOffset: options.display_defaults.poi.any.iconYOffset
                    }

                    // кроме проблем, упомянутых в
                    entity = L.marker(coords, {
                        id: options.id,
                        title: options.title,
                        layer: options.layer,
                        type: 'poi',
                        coords: options.coords,
                        keyboard: false,
                        icon: L.icon.fontAwesome({
                            iconClasses: `fa ${fa.icon}`,
                            markerColor: fa.markerColor,
                            iconColor: fa.iconColor,
                            iconXOffset: fa.iconXOffset,
                            iconYOffset: fa.iconYOffset,
                        }),
                        poi: options.poi
                    });

                    break;
                }
                //@todo: СЮДА НАДО ДОБАВЛЯТЬ НОВЫЕ ТИПЫ ОБЪЕКТОВ НА КАРТЕ
            }

            if (entity) {
                dataset[ key ] = entity;
            }
        } );

        this.regionsDataset = dataset;

        return dataset;
    }

    /**
     * Возвращает Windows Location Hash Link
     *
     * @param id
     * @param action
     * @returns {string}
     */
    static WLH_makeLink(id, action = 'view') {
        return `#${action}=[${id}]`;
    }

    /**
     * Анализируем Window.Location.Hash и определяем опции фокусировки/показа региона.
     * Возвращаем опции действия.
     *
     * Следует учитывать, что на карте может не быть региона, переданного в параметрах. Для обработки этой ситуации
     * передается массив карты (и имя текущего слоя?).
     *
     * @param dataset
     * @returns {action, id_region}
     */
    static WLH_getAction(dataset) {
        let regexp_pattern = /(view|focus)=\[(.*)\]/;
        let wlh = window.location.hash;
        let wlh_params = wlh.match(regexp_pattern);
        let options = {
            action: null,
            id_region: null
        };

        if (
            ((wlh.length > 1) && (wlh_params !== null))
            &&
            (((wlh_params[1] === 'view') || (wlh_params[1] === 'focus')) && (wlh_params[2] !== ''))
            &&
            ( wlh_params[2] in dataset )
        ) {
            options = {};
            options.action = wlh_params[1];
            options.id_region = wlh_params[2];
        }
        return options;
    }

    /**
     * Загружает контент из БД и записывает его в контейнер infoBox
     *
     * @param target
     * @param id_region
     * @returns {boolean}
     */
    loadContent(id_region, target = 'section-infobox-content') {
        if (!(id_region in this.regionsDataset)) {
            console.log(`[${id_region}] not found at regionsDataset.`);
            return false;
        }
        let $target = $(`#${target}`);

        if (this.IS_DEBUG) console.log(`Called do_LoadContent for ${id_region}`);

        if (MapManager.current_infobox_region_id !== id_region) {
            let url = MapManager.makeURL('view', this.theMap['id'], id_region, false);

            $target.html('');

            $.get(url, function(){ }).done(function(data){
                if (this.IS_DEBUG) console.log(`data loaded, length ${data.length}`);

                MapManager.current_infobox_region_id = id_region;

                $target
                    .html(data)
                    .scrollTop(0)
                ;
                // scroll box to top
                // document.getElementById(target).scrollTop = 0;
            });
        }
    }

    /**
     * Управляет поведением контейнера infoBox
     *
     * @param event
     * @param id_region
     */
    manageInfoBox(event, id_region) {
        if (!MapManager.__InfoBox) {
            MapManager.__InfoBox = new L.Control.InfoBox();
            map.addControl( MapManager.__InfoBox );
        }

        let $infobox = $("#section-infobox");
        let $infobox_toggle_button = $('#actor-section-infobox-toggle');
        let current_infobox_visible_state = $infobox_toggle_button.data('content-visibility');

        switch (event) {
            case 'show': {
                this.loadContent(id_region);

                window.location.hash = MapManager.WLH_makeLink(id_region);

                current_infobox_visible_state = true;

                $infobox.show();
                break;
            }
            case 'hide': {
                current_infobox_visible_state = false;

                history.pushState('', document.title, window.location.pathname);

                $infobox.hide();
                break;
            }
            case 'toggle': {
                if (current_infobox_visible_state) {
                    history.pushState('', document.title, window.location.pathname);
                    current_infobox_visible_state = false;
                } else {
                    current_infobox_visible_state = true;
                    window.location.hash = MapManager.WLH_makeLink(id_region);
                }
                $infobox.toggle();
                break;
            }
        }

        $infobox_toggle_button.data('content-visibility', current_infobox_visible_state);
    }

    /**
     * Показывает контентное окно colorbox'ом
     *
     * @param id_region
     * @param title
     */
    showContentColorBox(id_region, title) {
        let url = MapManager.makeURL(
            'view',
            this.theMap['id'],
            id_region,
            ((window != window.top || document != top.document || self.location != top.location))
        );

        $.get( url, function() {

        }).done(function(data) {
            let colorbox_width  = 800;
            let colorbox_height = 600;

            $.colorbox({
                html: data,
                width: colorbox_width,
                height: colorbox_height,
                title: title,
                onClosed: function(){
                    history.pushState('', document.title, window.location.pathname);
                }
            });
        });
    }

    /**
     * Генерирует URL для действия
     *
     * @param action
     * @param map_alias
     * @param id_region
     * @param is_iframe
     * @returns {string}
     */
    static makeURL(action = 'view', map_alias, id_region, is_iframe = false) {
        let _act = null;
        switch (action) {
            case 'view': {
                _act = window.REGION_URLS['view'];
                break;
            }
            case 'edit': {
                _act = window.REGION_URLS['edit'];
                break;
            }
        }
        return `${_act}?map=${map_alias}&id=${id_region}${ is_iframe ? '&resultType=iframe' : '' }`;
    }

}