/**
 * Функции, относящиеся к просмотру карты
 */

/* ==================================================== show content ==================================================== */

/**
 * Показывает контентное окно colorbox'ом.
 *
 *
 * @param id_region
 * @param title
 *
 * @global map_alias, colorbox_width, colorbox_height
 */
showContentColorbox = function(id_region , title) {
    var is_iframe = ((window != window.top || document != top.document || self.location != top.location)) ? '&resultType=iframe' : '';
    var url = '/api/get/regiondata?map=' + map_alias + '&id=' + id_region + is_iframe;

    $.get( url, function() {
    }).done(function(data) {
        var colorbox_width  =   /*theMap['colorbox']['width']      || */ 800;
        var colorbox_height =   /* theMap['colorbox']['height']     ||*/ 600;
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

toggleContentViewBox = function(id_region, layer) {
    var url = '/api/get/regiondata?map=' + map_alias + '&id=' + id_region;
    if (current_infobox_region_id == id_region) {
        toggleInfoBox('#actor-viewbox-toggle');

        if ( $('#actor-viewbox-toggle').data('content-is-visible') == false ) {
            history.pushState('', document.title, window.location.pathname);
        } else {
            window.location.hash = "#view=[" + layer + '|' + id_region + "]";
        }

    } else {
        current_infobox_region_id = id_region;
        $("#section-info-content").html('');
        document.getElementById('section-info-content').scrollTop = 0; // scroll box to top

        $.ajax({
            url: url,
            type: 'GET',
            async: false
        }).done(function(data){
            if (!find_LayerWithRegion(id_region)) {
                console.log("[" + id_region + "] not found at Layer " + layer + " at polymap.");
                return false;
            }

            var region_center = polymap[layer][ id_region ].getBounds().getCenter();

            $("#actor-viewbox-toggle").data('content-is-visible', true).html("Скрыть");
            $("#section-info-content").html(data).show();

        });
    }
}

showContentViewBox = function(id_region, title) {
    var url = '/api/get/regiondata?map=' + map_alias + '&id=' + id_region;

    $.get(url, function(){}).done(function(data){
        map.setZoom( theMap['display']['zoom'] );

        // move center:
        // (минус - вправо, плюс - влево -- потому что сдвигаем саму карту, а не регион)
        // если инфоблок слева, регионы справа - то "-"
        // если инфоблок справа, регионы слева - то "+"
        if (!(id_region in polymap)) {
            console.log("[" + id_region + "] not found at Polymap ");
            return false;
        }

        var region_center = polymap [ id_region ].getBounds().getCenter();
        var region_center_shifting_method = template_orientation || 0;
        region_center.lng = region_center.lng + (region_center_shifting_method * map_centring_panning_step);

        map.panTo( region_center, { animate: true, duration: 0.5, noMoveStart: true} );

        $("#section-info-content").html(data).show();
        $("#actor-viewbox-toggle").data('content-is-visible', true).html("Скрыть");
    });
}

/* ==================================================== end: show content ==================================================== */

/* ==================================================== toggle ==================================================== */

toggleRegionsBox = function(el) {
    var state = $(el).data('content-is-visible');
    var text = (state == false) ? '&nbsp;Скрыть&nbsp;' : 'Показать';
    $(el).html(text);

    var data = $(el).data('content');
    $('#' + data).toggle();
    $('#sort-select').toggle();
    $(el).data('content-is-visible', !state);
};
toggleInfoBox = function(el) {
    var state = $(el).data('content-is-visible');
    var text = (state == false) ? '&nbsp;Скрыть&nbsp;' : 'Показать';
    $(el).html(text);

    var data = $(el).data('content');
    $('#' + data).toggle();
    $(el).data('content-is-visible', !state);
}

/* ==================================================== end: toggle ==================================================== */

/* ==================================================== create map regions ==================================================== */

buildRegionsAtLayer = function(layer_data) {
    var set_of_regions = Object.create(null);

    Object.keys( layer_data.regions ).forEach(function( key ){
        var region = layer_data.regions[ key ];
        var type = region['type'];
        var coords = region['coords'];

        // DEFAULTS for ALL polygons
        var options = {
            color: region['borderColor']        || theMap.region_defaults_empty.borderColor,
            weight: region['borderWidth']       || theMap.region_defaults_empty.borderWidth,
            opacity: region['borderOpacity']    || theMap.region_defaults_empty.borderOpacity,
            fillColor: region['fillColor']      || theMap.region_defaults_empty.fillColor,
            fillOpacity: region['fillOpacity']  || theMap.region_defaults_empty.fillOpacity,
            radius: region['radius']            || 10
        };

        var entity;
        if (type == 'polygon') {
            entity = L.polygon(coords, options);
        } else if (type == 'rect') {
            entity = L.rectangle(coords, options);
        } else if (type == 'circle') {
            entity = L.circle(coords, options)
        }

        set_of_regions[ key ] = entity;
    } );

    return set_of_regions;
}

/**
 * Возвращает объект, содержащий все регионы.
 *
 * Аргумент layer в 0.5.8 не используется
 *
 * @param theMap
 * @param layer
 * @returns {Object}
 */
buildPolymap = function(theMap, layer) {
    var polymap = Object.create(null);

    Object.keys( theMap.regions ).forEach(function( key ){
        var region = theMap.regions[ key ];
        var type = region['type'];
        var coords = region['coords'];

        // DEFAULTS for ALL polygons
        var options = {
            color: region['borderColor']      ||  theMap.region_defaults_empty.borderColor,
            weight: region['borderWidth']      ||  theMap.region_defaults_empty.borderWidth,
            opacity: region['borderOpacity']    ||  theMap.region_defaults_empty.borderOpacity,
            fillColor: region['fillColor']  ||  theMap.region_defaults_empty.fillColor,
            fillOpacity: region['fillOpacity'] || theMap.region_defaults_empty.fillOpacity,
            radius: region['radius'] || 10
        };

        var entity;
        if (type == 'polygon') {
            entity = L.polygon(coords, options);
        } else if (type == 'rect') {
            entity = L.rectangle(coords, options);
        } else if (type == 'circle') {
            entity = L.circle(coords, options)
        }

        polymap[ key ] = entity;
    } );

    return polymap;
}

/* ==================================================== end: create map regions ==================================================== */

/* ==================================================== bind-action-focus-region ==================================================== */

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
wlhBased_GetAction = function(polymap, layer) {
    var wlh = window.location.hash;
    var wlh_params = wlh.match(/(view|focus)=\[(.*)\|(.*)\]/);
    var options = false;

    if (
        ((wlh.length > 1) && (wlh_params !== null))
        &&
        (((wlh_params[1] == 'view') || (wlh_params[1] == 'focus')) && (wlh_params[2] != ''))
        &&
        ( wlh_params[2] in polymap )
        &&
        ( wlh_params[3] in polymap[ wlh_params[2] ])
    ) {
        options = {};
        options.action = wlh_params[1];
        options.layer = wlh_params[2];
        options.id_region = wlh_params[3];
    }
    return options;
};

/* ====================================================  *  ==================================================== */

/* ==================================================== WLH ==================================================== */
/**
 * Анализируем options и показываем инфу по региону, если это нужно
 *
 * @param options
 * @returns {boolean}
 */
do_RegionShowInfo = function(options) {
    if (options && options.action == 'view') {
        var id_region = options.id_region;
        var id_layer = options.layer;
        var url = '/api/get/regiondata?map=' + map_alias + '&id=' + id_region;

        if (!find_LayerWithRegion(id_region)) {
            console.log("[" + id_region + "] not found at Layer " + id_layer + " at polymap.");
            return false;
        }

        $.get(url, function(){}).done(function(data){

            // move center:
            // (минус - вправо, плюс - влево -- потому что сдвигаем саму карту, а не регион)
            // если инфоблок слева, регионы справа - то "-"
            // если инфоблок справа, регионы слева - то "+"
            var focus_animate_duration = theMap['display']['focus_animate_duration'] || 0.5;

            var region_center = polymap[ id_layer ][ id_region ].getBounds().getCenter();
            var region_center_shifting_method = template_orientation;

            region_center.lng = region_center.lng + (region_center_shifting_method * map_centring_panning_step);

            map.panTo( region_center, { animate: true, duration: focus_animate_duration, noMoveStart: true} );

            $("#section-info-content").html(data).show();
            $("#actor-viewbox-toggle").data('content-is-visible', true).html("Скрыть");
        });
        return true;
    }
};

find_LayerWithRegion = function(id_region){
    let found_layer = false;
    Object.keys( polymap ).forEach(function(layer){
        if (id_region in polymap[layer]) found_layer = layer;
    });

    return found_layer;
}

/**
 * Анализирует options и выполняем фокусировку на регион.
 *
 * Опции могут быть сгенерированы как методом wlhBased_GetAction(), так и сделаны вручную, например для .on('click', '.action-focus-at-region'...)
 *
 * @param options
 * @param polymap
 * @param layer
 * @returns {boolean}
 */
do_RegionFocus = function(options) {
    if (options && options.action == 'focus') {
        var id_region = options.id_region;
        var id_layer = options.layer;

        if (!find_LayerWithRegion(id_region)) {
            console.log("[" + id_region + "] not found at Layer " + id_layer + " at polymap.");
            return false;
        }

        var wlh_region_bounds = polymap[ id_layer ] [ id_region ].getBounds();
        var old_style = polymap[ id_layer ][ id_region ].options['fillColor'];
        var focus_animate_duration = theMap['display']['focus_animate_duration'] || 0.7;
        var focus_highlight_color = theMap['display']['focus_highlight_color'] || '#ff0000';
        var focus_timeout = theMap['display']['focus_timeout'] || 1000;

        map.panTo( wlh_region_bounds.getCenter(), { animate: true, duration: focus_animate_duration, noMoveStart: true});
        polymap[ id_layer ][ id_region ].setStyle({fillColor: focus_highlight_color});

        setTimeout(function(){
            polymap[ id_layer ][ id_region ].setStyle({fillColor: old_style});
        }, focus_timeout);
        return true;
    }
};
/* ==================================================== end: bind-action-focus-region ==================================================== */

/* ==================================================== begin: create controls ==================================================== */
/**
 * Создает в объекте L Control-элемент: имя региона (для карт типа folio)
 */
createControl_RegionTitle = function(pos){
    // return L.control.extend делать нельзя (ошибка TypeError: t.addTo is not a function )
    L.Control.Title = L.Control.extend({
        options: {
            position: pos || 'topleft'
        },
        onAdd: function(map) {
            var div = L.DomUtil.get('section-region-title');
            L.DomUtil.removeClass(div, 'invisible');
            L.DomEvent.disableScrollPropagation(div);
            L.DomEvent.disableClickPropagation(div);
            return div;
        },
        onRemove: function(map){}
    });
};


/**
 * Создает в объекте L Control-элемент: список регионов (только создает
 */
createControl_RegionsBox = function() {
    L.Control.RegionsBox = L.Control.extend({
        is_content_visible: false,
        options: {
            position: $("#section-regions").data('leaflet-control-position')
        },
        onAdd: function(map) {
            var div = L.DomUtil.get('section-regions');
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
    L.Control.InfoBox = L.Control.extend({
        is_content_visible: false,
        options: {
            position: $("#section-infobox").data('leaflet-control-position')
        },
        onAdd: function(map) {
            var div = L.DomUtil.get('section-infobox');
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
createControl_Backward = function(pos){
    L.Control.Backward = L.Control.extend({
        options: {
            position: pos || 'bottomleft'
        },
        onAdd: function(map) {
            var div = L.DomUtil.get('section-backward');
            L.DomUtil.removeClass(div, 'invisible');
            L.DomEvent.disableScrollPropagation(div);
            L.DomEvent.disableClickPropagation(div);
            return div;
        },
        onRemove: function(map){}
    });
};
/* ==================================================== end: create controls ==================================================== */

/* DEPRECATED METHODS */

if (false){
    // id="bind-actor-click-inside-colorbox"
    // обрабатываем клик по ссылке внутри попап окна
    // (на самом деле надо проверять, это ссылка на ту же карту или нет?)
    //@todo: протестировать, отладить!
    // причем только внутри колорбокса
    $(document).on('click', '#cboxLoadedContent a', function(){ // здесь другой элемент ловит событие!
        var href = $(this).attr('href');
        var wlh = window.location.href;

        if (href.indexOf( '#view' ) == 0) { // если href содержит ссылку на блок с информацией...
            var href_params = href.match(/view=\[(.*)\]/);
            if (href_params != null) {
                history.pushState('', document.title, window.location.pathname + href);

                showContentBox(href_params[1], '');
            }
        } else {
            window.location.assign(href);
            window.location.reload(true);
        }

        return false;
    });
}

/* === Panes === */
pane_show = function(id) {
    map_panes[id].style.display = '';
};
pane_hide = function(id) {
    map_panes[id].style.display = 'none';
};

Number.prototype.between = function(a, b) {
    var min = Math.min.apply(Math, [a, b]),
        max = Math.max.apply(Math, [a, b]);
    return this > min && this < max;
};

Number.prototype.inbound = function(a, b) {
    var min = Math.min.apply(Math, [a, b]),
        max = Math.max.apply(Math, [a, b]);
    return this >= min && this <= max;
};
