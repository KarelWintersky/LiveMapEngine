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
    if (!(id_region in regionsDataset)) {
        console.log("[" + id_region + "] not found at regionsDataset.");
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
    if (!MapManager.__InfoBox) {
        MapManager.__InfoBox = new L.Control.InfoBox();
        map.addControl( MapManager.__InfoBox );
    }

    let $infobox = $("#section-infobox");
    let $infobox_toggle_buttpon = $('#actor-section-infobox-toggle');
    let current_infobox_visible_state = $infobox_toggle_buttpon.data('content-visibility');

    switch (event) {
        case 'show': {
            do_LoadContent(id_region);

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
    let old_style = regionsDataset[id_region].options['fillColor'];

    if (!is_visible) {
        map.setZoom( theMap['layers'][id_layer]['zoom'], {
            animate: false
        } );
        bounds = regionsDataset[id_region].getBounds();

        regionsDataset[ id_region ].setStyle({fillColor: focus_highlight_color}); // подсвечиваем (перенести в функцию/метод объекта)

        map.panTo( bounds.getCenter(), { animate: true, duration: 1, noMoveStart: true});
    } else {
        bounds = regionsDataset[id_region].getBounds();

        regionsDataset[ id_region ].setStyle({fillColor: focus_highlight_color}); // подсвечиваем (перенести в функцию/метод объекта)

        map.panTo( bounds.getCenter(), { animate: true, duration: 1, noMoveStart: true});
    }

    // восстанавливаем по таймауту
    setTimeout(function(){
        regionsDataset[id_region].setStyle({fillColor: old_style});
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
    let region = regionsDataset[id_region];

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




/* ==================================================== end: create controls ========================================= */

/* ==================================================== create map regions =========================================== */
/**
 * Возвращает объект, содержащий все регионы.
 * Используется только в folio и colorbox
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

        let options = Object.create(null);
        options = {
            id: region.id,
            title: region.title || region.id,
            coords: coords,
            layer: layer,
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
        }
    } );

    return polymap;
}

/* ==================================================== end: create map regions ====================================== */

/* ==================================================== Window Location Hash based =================================== */

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

