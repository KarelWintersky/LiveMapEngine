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
        var colorbox_width  =   theMap.colorbox.width      || 800;
        var colorbox_height =   theMap.colorbox.height     || 600;
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

toggleContentViewBox = function(id_region, title) {
    var url = '/api/get/regiondata?map=' + map_alias + '&id=' + id_region;
    if (current_infobox_region_id == id_region) {
        toggleInfoBox('#actor-viewbox-toggle');

        if ( $('#actor-viewbox-toggle').data('content-is-visible') == false ) {
            history.pushState('', document.title, window.location.pathname);
        } else {
            window.location.hash = "#view=[" + id_region + "]";
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
            var region_center = polymap [ id_region ].getBounds().getCenter();

            // сдвиг происходит только если регион слишком близко к центру (ближе 70 пикселей)
            if (map_centring_panning_step > 0) {
                if (region_center.lng > map.getBounds().getCenter().lng ) {
                    region_center.lng += map_centring_panning_step;
                    map.panTo( region_center, { animate: true, duration: 0.5, noMoveStart: true} );
                }
            } else {
                if (region_center.lng <= map.getBounds().getCenter().lng ) {
                    region_center.lng += map_centring_panning_step;
                    map.panTo( region_center, { animate: true, duration: 0.5, noMoveStart: true} );
                }
            }

            $("#actor-viewbox-toggle").data('content-is-visible', true).html("Скрыть");
            $("#section-info-content").html(data).show();

        });
    }
}

showContentViewBox = function(id_region, title) {
    var url = '/api/get/regiondata?map=' + map_alias + '&id=' + id_region;

    $.get(url, function(){}).done(function(data){
        var region_center = polymap [ id_region ].getBounds().getCenter();
        region_center.lng -= 50; // move center to right (50px)
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
            color: region['color']      ||  theMap.defaults.polygon_color,
            width: region['width']      ||  theMap.defaults.polygon_width,
            opacity: region['opacity']    ||  theMap.defaults.polygon_opacity,
            fillColor: region['fillColor']  ||  theMap.defaults.polygon_fillColor,
            fillOpacity: region['fillOpacity'] || theMap.defaults.polygon_fillOpacity,
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
    var wlh_params = wlh.match(/(view|focus)=\[(.*)\]/);
    var options = false;

    if (
        ((wlh.length > 1) && (wlh_params !== null))
        &&
        (((wlh_params[1] == 'view') || (wlh_params[1] == 'focus')) && (wlh_params[2] != ''))
        &&
        ( wlh_params[2] in polymap )
    ) {
        options = {};
        options.action = wlh_params[1];
        options.region_id = wlh_params[2];
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
wlhBased_RegionShowInfo = function(options) {
    if (options && options.action == 'view') {
        showContentViewBox( options.region_id );
        return true;
    }
};

/**
 * Анализирует options и выполняем фокусировку на регион.
 *
 * @todo: Переименовать: метод на самом деле не имеет никакой привязки к WLH и работает на основании переданных опций.
 * Опции могут быть сгенерированы как методом wlhBased_GetAction(), так и сделаны вручную, например для .on('click', '.action-focus-at-region'...)
 *
 * @param options
 * @param polymap
 * @param layer
 * @returns {boolean}
 */
wlhBased_RegionFocus = function(options, polymap, layer) {
    if (options && options.action == 'focus') {
        var wlh_region_bounds = polymap [ options.region_id ].getBounds();
        var old_style = polymap[ options.region_id ].options['fillColor']; //@todo: уточнить имя опции при переходе к ExtendedLayerOptions
        map.panTo( wlh_region_bounds.getCenter(), { animate: true, duration: 0.7, noMoveStart: true}); //@todo: OPTIONS->VIEWPORT->focus_animate_duration
        polymap[ options.region_id ].setStyle({fillColor: '#ff0000'}); //@todo: OPTIONS->VIEWPORT->focus_highlight_color

        setTimeout(function(){
            polymap[ options.region_id ].setStyle({fillColor: old_style});
        }, 1000); //@todo: OPTIONS->VIEWPORT->focus_timeout || 1000
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