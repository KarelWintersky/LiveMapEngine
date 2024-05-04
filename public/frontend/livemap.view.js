const URL_GET_REGION_CONTENT = '/region/get?map=';

Number.prototype.between = function(a, b) {
    let min = Math.min.apply(Math, [a, b]),
        max = Math.max.apply(Math, [a, b]);
    return this > min && this < max;
};

Number.prototype.inbound = function(a, b) {
    let min = Math.min.apply(Math, [a, b]),
        max = Math.max.apply(Math, [a, b]);
    return this >= min && this <= max;
};

$.fn.escape = function (callback) {
    return this.each(function () {
        $(document).on("keydown", this, function (e) {
            let keycode = ((typeof e.keyCode != 'undefined' && e.keyCode) ? e.keyCode : e.which);
            if (keycode === 27) {
                callback.call(this, e);
            }
        });
    });
};
/* ==================================================== create map =================================================== */
setup_MapCreate = function(target, theMap, options = {}) {
    let map = null;

    let use_zoom_slider;
    let use_zoom_slider_position = options['zoom_slider_position'] || 'bottomright';

    let _options = {
        crs: L.CRS.Simple,
        minZoom: theMap['display']['zoom_min'],
        maxZoom: theMap['display']['zoom_max'],
    };

    let use_canvas = true;

    if (use_canvas) {
        _options.preferCanvas = true;
        _options.renderer = L.canvas();
    } else {
        _options.preferCanvas = false;
        _options.renderer = L.svg({ padding: 3 }); // должно быть, походу, maxzoom+1
    }

    switch (options['zoom_mode']) {
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

    switch (theMap.map.type) {
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
}

setup_MapSetMaxBounds = function(map, theMap) {
    let bounds = [
        [0, 0],
        [theMap['map']['height'], theMap['map']['width']]
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
    // map.setMaxBounds(bounds);

    // map.setView();

    return bounds;
}

/**
 * Должно возвращать новый слой, который мы должны добавлять на карту вне функции
 *
 *
 * @param map
 * @param theMap
 * @param bounds
 * @returns {null}
 */
setup_MapCreateOverlay = function(map, theMap, bounds) {
    let image = null;

    switch (theMap.map.type) {
        case 'bitmap': {
            image = L.imageOverlay( theMap['map']['imagefile'], base_map_bounds).addTo(map);
            break;
        }
        case 'vector': {
            image = L.imageOverlay( theMap['map']['imagefile'], base_map_bounds).addTo(map);
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
            }).addTo(map);

            break;
        }
    }

    return image;

};


/* ==================================================== show content ================================================= */

/**
 * Показывает контентное окно colorbox'ом.
 *
 * @param id_region
 * @param title
 *
 * @global map_alias, colorbox_width, colorbox_height
 */
showContentColorbox = function(id_region , title) {
    let is_iframe = ((window != window.top || document != top.document || self.location != top.location)) ? '&resultType=iframe' : '';
    let url = `${URL_GET_REGION_CONTENT}${map_alias}&id=${id_region}${is_iframe}`

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

do_LoadContent = function(id_region) {
    if (!(id_region in polymap)) {
        console.log("[" + id_region + "] not found at polymap.");
        return false;
    }

    if (IS_DEBUG) console.log("Called do_LoadContent for " + id_region);

    if (current_infobox_region_id !== id_region) {
        let url = URL_GET_REGION_CONTENT + map_alias + '&id=' + id_region;

        $("#section-infobox-content").html('');

        $.get(url, function(){}).done(function(data){
            if (IS_DEBUG) console.log('data loaded, length ' + data.length);

            current_infobox_region_id = id_region;

            $("#section-infobox-content").html(data);
            document.getElementById('section-infobox-content').scrollTop = 0; // scroll box to top
        });
    }
}

manageInfoBox = function(event, id_region) {
    if (!__InfoBox) {
        __InfoBox = new L.Control.InfoBox();
        map.addControl( __InfoBox );
    }

    let $infobox = $("#section-infobox");
    let $infobox_toggle_buttpon = $('#actor-section-infobox-toggle');
    let current_infobox_visible_state = $infobox_toggle_buttpon.data('content-visibility');

    // if (IS_DEBUG) console.log("Event: " + event + " for region " + id_region);
    // if (IS_DEBUG) console.log('Current infobox visibility state: ' + current_infobox_visible_state);

    switch (event) {
        case 'show': {
            do_LoadContent(id_region);

            window.location.hash = "#view=[" + id_region + "]";

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
                window.location.hash = "#view=[" + id_region + "]";
            }
            $infobox.toggle();
            break;
        }
    }
    $infobox_toggle_buttpon.data('content-visibility', current_infobox_visible_state);
}


/* ==================================================== end: show content ============================================ */

do_HighlightRegion = function(id_region) {

}




/* ==================================================== begin: focus ================================================= */
onclick_FocusRegion = function(id_region){
    let id_layer = theMap['regions'][id_region]['layer'];
    let is_visible = LGS[id_layer].visible;
    let bounds;

    // if (IS_DEBUG) console.log("onclick_FocusRegion -> layer " + id_layer + " is_visible " + is_visible);
    // if (IS_DEBUG) console.log( LGS[id_layer].actor );

    // сохраняем оригинальный стиль региона
    let old_style = polymap[id_region].options['fillColor'];

    if (!is_visible) {
        map.setZoom( theMap['layers'][id_layer]['zoom'], {
            animate: false
        } );
        bounds = polymap[id_region].getBounds();

        polymap[ id_region ].setStyle({fillColor: focus_highlight_color}); // подсвечиваем (перенести в функцию/метод объекта)

        map.panTo( bounds.getCenter(), { animate: true, duration: 1, noMoveStart: true});
    } else {
        bounds = polymap[id_region].getBounds();

        polymap[ id_region ].setStyle({fillColor: focus_highlight_color}); // подсвечиваем (перенести в функцию/метод объекта)

        map.panTo( bounds.getCenter(), { animate: true, duration: 1, noMoveStart: true});
    }

    // восстанавливаем по таймауту
    setTimeout(function(){
        polymap[id_region].setStyle({fillColor: old_style});
    }, focus_timeout);


};

wlh_FocusRegion = function(id_region){
    /* позиционируем */
    let id_layer = theMap['regions'][id_region]['layer'];
    let is_visible = LGS[id_layer].visible;
    let bounds;

    if (IS_DEBUG) console.log("Текущий зум: ", map.getZoom());
    if (IS_DEBUG) console.log("Запрашиваемый регион: " , id_region);
    if (IS_DEBUG) console.log("принадлежит группе слоёв " , id_layer);
    if (IS_DEBUG) console.log("Видимость группы слоёв с регионом: " , is_visible);
    if (IS_DEBUG) console.log("Описание группы слоёв: ", LGS[id_layer]);

    let zmin = LGS[id_layer].zoom_min;
    let zmax = LGS[id_layer].zoom_max;

    if (IS_DEBUG) console.log("Зум слоя (из инфо карты)", theMap['layers'][id_layer]['zoom']);
    if (IS_DEBUG) console.log("Зум слоя (из layergroup)", LGS[id_layer]['zoom']);

    let currentZoom = map.getZoom();

    // добавляем все слои
    Object.keys( LGS ).forEach(function(lg){
        map.addLayer( LGS[lg].actor );
        LGS[lg].visible = true;
    });

    map.fitBounds(base_map_bounds);

    map.setZoom( theMap.display.zoom, {
        animate: false
    } );

    // пан
    let region = polymap[id_region];

    if (region.options.value == 'poi') {
        bounds = region._latlng;
        map.panTo( bounds, { animate: false, duration: 1, noMoveStart: true});
    } else {
        bounds = region.getBounds();
        map.panTo( bounds.getCenter(), { animate: false, duration: 1, noMoveStart: true});
    }

    // удаляем все невидные слои
    Object.keys( LGS ).forEach(function(lg){
        if (!(theMap['layers'][id_layer]['zoom'].inbound(zmin, zmax))) {
            if (IS_DEBUG) console.log('Надо скрыть слой ' + lg);

            map.removeLayer( LGS[id_layer].actor );
            LGS[id_layer].visible = false;
        }
    });

}



/* ==================================================== end: focus =================================================== */

/* ==================================================== begin: create controls ======================================= */
/**
 * Создает в объекте L Control-элемент: имя региона (для карт типа folio)
 */
createControl_RegionTitle = function(position){
    if ($("#section-region-title").length == 0) {
        return false;
    }

    // return L.control.extend делать нельзя (ошибка TypeError: t.addTo is not a function )
    L.Control.Title = L.Control.extend({
        options: {
            position: position || 'topleft'
        },
        onAdd: function(map) {
            let div = L.DomUtil.get('section-region-title');
            L.DomUtil.removeClass(div, 'invisible');
            L.DomEvent.disableScrollPropagation(div);
            L.DomEvent.disableClickPropagation(div);
            return div;
        },
        onRemove: function(map){}
    });
};

/**
 * Создает в объекте L Control-элемент: список регионов
 */
createControl_RegionsBox = function() {
    if ($("#section-regions").length == 0) {
        return false;
    }

    L.Control.RegionsBox = L.Control.extend({
        is_content_visible: false,
        options: {
            position: $("#section-regions").data('leaflet-control-position')
        },
        onAdd: function(map) {
            let div = L.DomUtil.get('section-regions');
            L.DomUtil.removeClass(div, 'invisible');
            L.DomUtil.enableTextSelection();
            L.DomEvent.disableScrollPropagation(div);
            L.DomEvent.disableClickPropagation(div);
            return div;
        },
        onRemove: function(map) {}
    });
};

/**
 * Создает в объекте L Control элемент: информация о регионе
 */
createControl_InfoBox = function(){
    if ($("#section-infobox").length == 0) {
        return false;
    }

    L.Control.InfoBox = L.Control.extend({
        is_content_visible: false,
        options: {
            position: $("#section-infobox").data('leaflet-control-position')
        },
        onAdd: function(map) {
            let div = L.DomUtil.get('section-infobox');
            L.DomUtil.removeClass(div, 'invisible');
            L.DomUtil.enableTextSelection();
            L.DomEvent.disableScrollPropagation(div);
            L.DomEvent.disableClickPropagation(div);
            return div;
        },
        onRemove: function(map) {}
    });
};

/**
 * Создает в объекте L Control элемент: кнопка "назад"
 */
createControl_Backward = function(position){
    if ($("#section-backward").length == 0) {
        return false;
    }

    L.Control.Backward = L.Control.extend({
        options: {
            position: position || 'bottomleft'
        },
        onAdd: function(map) {
            let div = L.DomUtil.get('section-backward');
            L.DomUtil.removeClass(div, 'invisible');
            L.DomEvent.disableScrollPropagation(div);
            L.DomEvent.disableClickPropagation(div);
            return div;
        },
        onRemove: function(map){}
    });
};

/* ==================================================== end: create controls ========================================= */

/* ==================================================== toggles ====================================================== */

toggleRegionsBox = function(el) {
    let state = $(el).data('content-is-visible');
    let text = (state == false) ? '&nbsp;Скрыть&nbsp;' : 'Показать';
    $(el).html(text);

    let data = $(el).data('content');
    $('#' + data).toggle();
    $('#sort-select').toggle();
    $(el).data('content-is-visible', !state);
};

toggleInfoBox = function(el) {
    let state = $(el).data('content-is-visible');
    let text = (state == false) ? '&nbsp;Скрыть&nbsp;' : 'Показать';
    $(el).html(text);

    let data = $(el).data('content');
    $('#' + data).toggle();
    $(el).data('content-is-visible', !state);
}

toggle_BackwardBox = function(el){
    let state = $(el).data('content-is-visible');
    let text = (state == false) ? '&lt;' : '&gt;';
    $(this).html(text);

    let data = $(el).data('content');
    $('#' + data).toggle();
    $(this).data('content-is-visible', !state);
}
/* ==================================================== end: toggles ================================================= */

/* ==================================================== create map regions =========================================== */
/**
 * Возвращает объект, содержащий все регионы.
 *
 * @param theMap
 * @returns {Object}
 */
buildPolymap = function(theMap) {
    let polymap = Object.create(null);

    Object.keys( theMap.regions ).forEach(function( key ) {
        let region = theMap.regions[key];
        let type = region['type'];
        let coords = region['coords'];
        let layer = region['layer'];

        // DEFAULTS for ALL polygons
        let options = Object.create(null);
        options = {
            id: region.id,
            title: region.title || region.id,
            coords: coords,
            radius: region['radius'] || 10,

            // present или empty - нужно брать из данных о регионе (пока что берётся present для всех регионов)
            /* параметры по-умолчанию для создания региона. В дальнейшем (on('mouseout'), on('mouseover') будем брать из структуры region */
            /* Это изменяемые параметры для региона. Они будут использованы для его создания */
            stroke: region['stroke'] || theMap.display.region.present.stroke,
            color: region['borderColor'] || theMap.display.region.present.borderColor,
            width: region['borderWidth'] || theMap.display.region.present.borderWidth,
            opacity: region['borderOpacity'] || theMap.display.region.present.borderOpacity,
            fill: region['fill'] || theMap.display.region.present.fill,
            fillColor: region['fillColor'] || theMap.display.region.present.fillColor,
            fillOpacity: region['fillOpacity'] || theMap.display.region.present.fillOpacity,

            /*
            А это неизменяемые параметры, они будут использованы для изменения стилей при событиях
            on('mouseover') и on('mouseout')
            * */
            display_defaults: {
                region: {
                    default: {
                        stroke: region['stroke'] || theMap.display.region.present.stroke,
                        color: region['borderColor'] || theMap.display.region.present.borderColor,
                        width: region['borderWidth'] || theMap.display.region.present.borderWidth,
                        opacity: region['borderOpacity'] || theMap.display.region.present.borderOpacity,
                        fill: region['fill'] || theMap.display.region.present.fill,
                        fillColor: region['fillColor'] || theMap.display.region.present.fillColor,
                        fillOpacity: region['fillOpacity'] || theMap.display.region.present.fillOpacity,
                    },
                    hover: {
                        stroke: theMap.display.region.present_hover.stroke,
                        borderColor: theMap.display.region.present_hover.borderColor,
                        borderWidth: theMap.display.region.present_hover.borderWidth,
                        borderOpacity: theMap.display.region.present_hover.borderOpacity,
                        fill: theMap.display.region.present_hover.fill,
                        fillColor: theMap.display.region.present_hover.fillColor,
                        fillOpacity: theMap.display.region.present_hover.fillOpacity,
                    }
                },
                poi: {
                    any: {
                        iconClass: theMap.display.poi.any.iconClass,
                        markerColor: theMap.display.poi.any.markerColor,
                        iconColor: theMap.display.poi.any.iconColor,
                        iconXOffset: theMap.display.poi.any.iconXOffset,
                        iconYOffset: theMap.display.poi.any.iconYOffset,
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
            },
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
            /*case 'marker': {
                break;
                options.type = 'poi';
                options.keyboard = false;

                let fa = {
                    icon: `fa ${options.poi.any.iconClass}`,
                    markerColor: options.poi.any.markerColor,
                    iconColor: options.poi.any.iconColor,
                    iconXOffset: options.poi.any.iconXOffset,
                    iconYOffset: options.poi.any.iconYOffset
                }

                // кроме проблем, упомянутых в
                entity = L.marker(coords, {
                    id: region.id,
                    title: region.title,
                    type: 'poi',
                    coords: coords,
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
            }*/
            //@todo: КАЖЕТСЯ СЮДА НАДО ДОБАВЛЯТЬ НОВЫЕ ТИПЫ ОБЪЕКТОВ НА КАРТЕ
        }

        if (entity) {
            polymap[ key ] = entity;
            console.log(entity);
        }
    } );

    return polymap;
}

/* ==================================================== end: create map regions ====================================== */

/* ==================================================== Window Location Hash based =================================== */

/**
 * Анализируетм Window.Location.Hash и определяем опции фокусировки/показа региона.
 * Возвращаем опции действия.
 *
 * Следует учитывать, что на карте может не быть региона, переданного в параметрах. Для обработки этой ситуации
 * передается массив карты и имя текущего слоя.
 *
 * @param polymap
 * @param layer
 * @returns {boolean}
 */
wlhBased_GetAction = function(polymap) {
    let wlh = window.location.hash;
    let wlh_params = wlh.match(/(view|focus)=\[(.*)\]/);
    let options = false;

    if (
        ((wlh.length > 1) && (wlh_params !== null))
        &&
        (((wlh_params[1] == 'view') || (wlh_params[1] == 'focus')) && (wlh_params[2] != ''))
        &&
        ( wlh_params[2] in polymap )
    ) {
        options = {};
        options.action = wlh_params[1];
        options.id_region = wlh_params[2];
        // options.layer = wlh_params[3];
    }
    return options;
}

/* ===================================== РЕФАКТОРИНГ ============================ */


do_ManageRegionContent = function(id_region) {

}

/* === DEPRECATED AND UNUSED CODE === */

if (false) {
    // id="bind-actor-click-inside-colorbox"
// обрабатываем клик по ссылке внутри попап окна
// (на самом деле надо проверять, это ссылка на ту же карту или нет?)
//@todo: протестировать, отладить!
    $(document).on('click', '#cboxLoadedContent a', function(){ // здесь другой элемент ловит событие!
        var href = $(this).attr('href');
        var wlh = window.location.href;

        if (href.indexOf( '#view' ) == 0) { // если href содержит ссылку на блок с информацией...
            var href_params = href.match(/view=\[(.*)\]/);
            if (href_params != null) {
                history.pushState('', document.title, window.location.pathname + href);
                toggleContentViewBox(href_params[1], '');
            }
        } else {
            window.location.assign(href);
            window.location.reload(true);
        }

        return false;
    });
}

