const URL_GETREGIONCONTENT = '/api/get/regiondata?map=';

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
    var is_iframe = ((window != window.top || document != top.document || self.location != top.location)) ? '&resultType=iframe' : '';
    var url = URL_GETREGIONCONTENT + map_alias + '&id=' + id_region + is_iframe;

    $.get( url, function() {
    }).done(function(data) {
        var colorbox_width  = 800;
        var colorbox_height = 600;
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

    console.log("Called do_LoadContent for " + id_region);

    if (current_infobox_region_id != id_region) {
        let url = URL_GETREGIONCONTENT + map_alias + '&id=' + id_region;

        $.get(url, function(){}).done(function(data){
            console.log('data loaded, length ' + data.length);
            $("#section-infobox-content").html(data);
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
    var current_infobox_visible_state = $infobox_toggle_buttpon.data('content-visibility');

    // console.log("Event: " + event + " for region " + id_region);
    // console.log('Current infobox visibility state: ' + current_infobox_visible_state);

    switch (event) {
        case 'show': {
            do_LoadContent(id_region);

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
            current_infobox_visible_state = !current_infobox_visible_state;
            $infobox.toggle();
            break;
        }
    }
    $infobox_toggle_buttpon.data('content-visibility', current_infobox_visible_state);
}


/* ==================================================== end: show content ============================================ */

/* ==================================================== begin: focus ================================================= */
onclick_FocusRegion = function(id_region){
    var id_layer = theMap['regions'][id_region]['layer'];
    var is_visible = LGS[id_layer].visible;
    var bounds;

    // console.log("onclick_FocusRegion -> layer " + id_layer + " is_visible " + is_visible);
    // console.log( LGS[id_layer].actor );

    if (!is_visible) {
        map.setZoom( theMap['layers'][id_layer]['zoom'], {
            animate: false
        } );
        bounds = polymap[id_region].getBounds();
        map.panTo( bounds.getCenter(), { animate: true, duration: 1, noMoveStart: true});
    } else {
        bounds = polymap[id_region].getBounds();
        map.panTo( bounds.getCenter(), { animate: true, duration: 1, noMoveStart: true});
    }
};

wlh_FocusRegion = function(id_region){
    /* позиционируем */
    var id_layer = theMap['regions'][id_region]['layer'];
    var is_visible = LGS[id_layer].visible;
    var bounds;

    console.log("Текущий зум: ", map.getZoom());
    console.log("Запрашиваемый регион: " , id_region);
    console.log("принадлежит группе слоёв " , id_layer);
    console.log("Видимость группы слоёв с регионом: " , is_visible);
    console.log("Описание группы слоёв: ", LGS[id_layer]);


    var zmin = LGS[id_layer].zoom_min;
    var zmax = LGS[id_layer].zoom_max;

    console.log("Зум слоя (из инфо карты)", theMap['layers'][id_layer]['zoom']);
    console.log("Зум слоя (из layergroup)", LGS[id_layer]['zoom']);

    var currentZoom = map.getZoom();

    // добавляем все слои
    Object.keys( LGS ).forEach(function(lg){
        map.addLayer( LGS[lg].actor );
        LGS[lg].visible = true;
    });

    map.fitBounds(base_map_bounds);

    // зум
    map.setZoom( LGS[id_layer]['zoom'], {
        animate: false
    });

    // пан
    bounds = polymap[id_region].getBounds();
    map.panTo( bounds.getCenter(), { animate: false, duration: 1, noMoveStart: true});

    // удаляем все невидные слои
    Object.keys( LGS ).forEach(function(lg){
        if (!(theMap['layers'][id_layer]['zoom'].inbound(zmin, zmax))) {
            console.log('Надо скрыть слой ' + lg);

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
    // return L.control.extend делать нельзя (ошибка TypeError: t.addTo is not a function )
    L.Control.Title = L.Control.extend({
        options: {
            position: position || 'topleft'
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
createControl_Backward = function(position){
    L.Control.Backward = L.Control.extend({
        options: {
            position: position || 'bottomleft'
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

/* ==================================================== end: create controls ========================================= */

/* ==================================================== toggles ====================================================== */

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
toggle_BackwardBox = function(el){
    var state = $(el).data('content-is-visible');
    var text = (state == false) ? '&lt;' : '&gt;';
    $(this).html(text);

    var data = $(el).data('content');
    $('#' + data).toggle();
    $(this).data('content-is-visible', !state);
}
/* ==================================================== end: toggles ================================================= */

/* ==================================================== create map regions =========================================== */
/**
 * Возвращает объект, содержащий все регионы.
 *
 * @param theMap
 * @param layer
 * @returns {Object}
 */
buildPolymap = function(theMap) {
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
        options.id_region = wlh_params[2];
        // options.layer = wlh_params[3];
    }
    return options;
}















do_ManageRegionContent = function(id_region) {

}