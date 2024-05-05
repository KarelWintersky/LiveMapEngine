/* ==================================================== begin: focus ================================================= */
onclick_FocusRegion = function(id_region){
    let id_layer = window.theMap['regions'][id_region]['layer'];
    let is_visible = LGS[id_layer].visible;
    let bounds;

    // if (IS_DEBUG) console.log("onclick_FocusRegion -> layer " + id_layer + " is_visible " + is_visible);
    // if (IS_DEBUG) console.log( LGS[id_layer].actor );

    // сохраняем оригинальный стиль региона
    let old_style = regionsDataset[id_region].options['fillColor'];

    if (!is_visible) {
        map.setZoom( window.theMap['layers'][id_layer]['zoom'], {
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
    let id_layer = window.theMap['regions'][id_region]['layer'];
    let is_visible = LGS[id_layer].visible;
    let bounds;

    if (IS_DEBUG) console.log("Текущий зум: ", map.getZoom());
    if (IS_DEBUG) console.log("Запрашиваемый регион: " , id_region);
    if (IS_DEBUG) console.log("принадлежит группе слоёв " , id_layer);
    if (IS_DEBUG) console.log("Видимость группы слоёв с регионом: " , is_visible);
    if (IS_DEBUG) console.log("Описание группы слоёв: ", LGS[id_layer]);

    let zmin = LGS[id_layer].zoom_min;
    let zmax = LGS[id_layer].zoom_max;

    if (IS_DEBUG) console.log("Зум слоя (из инфо карты)", window.theMap['layers'][id_layer]['zoom']);
    if (IS_DEBUG) console.log("Зум слоя (из layergroup)", LGS[id_layer]['zoom']);

    let currentZoom = map.getZoom();

    // добавляем все слои
    Object.keys( LGS ).forEach(function(lg){
        map.addLayer( LGS[lg].actor );
        LGS[lg].visible = true;
    });

    map.fitBounds(base_map_bounds);

    map.setZoom( window.theMap.display.zoom, {
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
        if (!(window.theMap['layers'][id_layer]['zoom'].inbound(zmin, zmax))) {
            if (IS_DEBUG) console.log('Надо скрыть слой ' + lg);

            map.removeLayer( LGS[id_layer].actor );
            LGS[id_layer].visible = false;
        }
    });

}

