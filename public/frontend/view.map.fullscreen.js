const focus_animate_duration = theMap['display']['focus_animate_duration'] || 0.7;
const focus_highlight_color = theMap['display']['focus_highlight_color'] || '#ff0000';
const focus_timeout = theMap['display']['focus_timeout'] || 1500;
const IS_DEBUG = false;
const DEBUG_SET_STYLE_WHILE_HOVER = false;

let current_infobox_region_id = '';
let map;
let base_map_bounds;
let LGS = Object.create(null);
let regionsDataset = Object.create(null);

/**
 * Инстанс infoBox
 * @type {null}
 * @private
 */
var __InfoBox = null;

$(function() {
    let _mapManager = new MapManager(theMap);
    let _mapContent = new MapContent(theMap);

    _mapManager.setBackgroundColor(".leaflet-container");

    map = _mapManager.createMap('map');

    base_map_bounds = _mapManager.getBounds();

    let image = _mapManager.createImageOverlay(base_map_bounds);
    image.addTo(map);

    map.setZoom( theMap['display']['zoom'] );

    // строим массив всех регионов
    regionsDataset = _mapManager.buildRegionsDataset();

    // биндим к каждому объекту функцию, показывающую информацию
    Object.keys( regionsDataset ).forEach(function(id_region){
        // обернуть в функцию и отрефакторить
        let map_element = regionsDataset[id_region];

        map_element.on('click', function() {
            // альтернатива - менять window.location.hash
            // а ниже отлавливать его изменения

            if (current_infobox_region_id == id_region) {
                manageInfoBox('toggle', id_region);
            } else {
                manageInfoBox('show', id_region);
            }
            current_infobox_region_id = id_region;

        }).on('mouseover', function() {
            // выставляем стили для региона при наведении на него мышки, для маркера типа POI стиль не ставится
            if (false === DEBUG_SET_STYLE_WHILE_HOVER) return;

            if (map_element.options.type != 'poi') {
                map_element.setStyle({
                    stroke: map_element.options.display_defaults.region.hover.stroke,
                    color: map_element.options.display_defaults.region.hover.borderColor,
                    weight: map_element.options.display_defaults.region.hover.borderWidth,
                    opacity: map_element.options.display_defaults.region.hover.borderOpacity,

                    fill: map_element.options.display_defaults.region.hover.fill,
                    fillColor: map_element.options.display_defaults.region.hover.fillColor,
                    fillOpacity: map_element.options.display_defaults.region.hover.fillOpacity,
                });
            } else {
                // Событие MOUSEOVER для L.Marker'а ловится корректно и позволяет изменить иконку элемента, НО...
                return false;
                map_element
                    .setIcon(L.icon.fontAwesome({
                    iconClasses: `fa ${map_element.options.poi.hover.iconClasses}`,
                    markerColor: map_element.options.poi.hover.markerColor,
                    iconColor: map_element.options.poi.hover.iconColor,
                    iconXOffset: map_element.options.poi.hover.iconXOffset,
                    iconYOffset: map_element.options.poi.hover.iconYOffset,
                }));
                // обработчик события закомментирован, поскольку событие MOUSEOUT НЕ ЛОВИТСЯ и поменять иконку обратно невозможно
                // возможно это баг плагина FontAwesomeIcon
            }

        }).on('mouseout', function() {

            if (false === DEBUG_SET_STYLE_WHILE_HOVER) return;

            // выставляем стили для региона при наведении при уходе с него мышки, для маркера типа POI стиль не ставится (по крайней мере не так)
            if (map_element.options.type != 'poi') {
                // region.setStyle({stroke: false, color: '#000000', weight: 0, opacity: 0});

                map_element.setStyle({
                    stroke: map_element.options.display_defaults.region.default.stroke,
                    color: map_element.options.display_defaults.region.default.borderColor,
                    weight: map_element.options.display_defaults.region.default.borderWidth,
                    opacity: map_element.options.display_defaults.region.default.borderOpacity,

                    fill: map_element.options.display_defaults.region.default.fill,
                    fillColor: map_element.options.display_defaults.region.default.fillColor,
                    fillOpacity: map_element.options.display_defaults.region.default.fillOpacity,
                });
            } else {
                return false;
                // событие MOUSEOUT НЕ ЛОВИТСЯ и поменять иконку обратно невозможно
                map_element.setIcon(L.icon.fontAwesome({
                    iconClasses: `fa ${map_element.options.poi.default.iconClass}`,
                    markerColor: map_element.options.poi.default.markerColor,
                    iconColor: map_element.options.poi.default.iconColor,
                    iconXOffset: map_element.options.poi.default.iconXOffset,
                    iconYOffset: map_element.options.poi.default.iconYOffset,
                }));
            }

        });
    });

    // раскладываем регионы по layer-группам
    Object.keys( regionsDataset ).forEach(function(id_region){
        let id_layer = theMap['regions'][id_region]['layer'];

        if (!(id_layer in LGS)) {
            let lg = new L.LayerGroup();
            LGS[ id_layer ] = {
                actor: lg,
                visible: false, // все слои скрыты
                zoom: theMap['layers'][id_layer]['zoom'],
                zoom_min: theMap['layers'][id_layer]['zoom_min'],
                zoom_max: theMap['layers'][id_layer]['zoom_max'],
            };
        }
        LGS[id_layer].actor.addLayer( regionsDataset[id_region] );
    });

    // показываем layer-группы или скрываем нужные
    Object.keys( LGS ).forEach(function(lg) {
        if (
            map.getZoom().inbound( LGS[lg].zoom_min, LGS[lg].zoom_max )
        ) {
            map.addLayer( LGS[lg].actor );
            LGS[lg].visible = true;
        } else {
            LGS[lg].actor.addTo(map);
            LGS[lg].actor.remove(); // map.addLayer( LGS[lg].actor );
            LGS[lg].visible = false;
        }
    });

    map.fitBounds(base_map_bounds);

    MapControls.declareControl_RegionsBox();
    MapControls.declareControl_InfoBox();
    MapControls.declareControl_Backward();

    if (! (window != window.top || document != top.document || self.location != top.location)) {
        // не показываем контрол "назад" если страница загружена в iframe
        map.addControl( new L.Control.Backward() );
    }

    // показываем список регионов только если он не пуст
    if (regions_with_content_ids.length) {
        map.addControl( new L.Control.RegionsBox() );
    }

    // анализируем window.location.hash
    if (true) {
        let wlh_options = MapManager.WLH_getAction(regionsDataset);
        if (wlh_options) {
            // было бы более интересным решением имитировать триггером клик по ссылке на регионе, но.. оно не работает
            // $("a.action-focus-at-region[data-region-id='" + wlh_options.id_region + "']").trigger('click');

            console.log(wlh_options);

            wlh_FocusRegion(wlh_options.id_region);
            manageInfoBox('show', wlh_options.id_region);
        } else {
        }
    }

    // отлавливаем зум
    map.on('zoomend', function() {
        let currentZoom = map.getZoom();
        console.log("Current zoom: " + currentZoom);

        Object.keys( LGS ).forEach(function(lg){
            let zmin = LGS[lg].zoom_min;
            let zmax = LGS[lg].zoom_max;

            if (currentZoom.inbound(zmin, zmax)) {
                map.addLayer( LGS[lg].actor );
                LGS[lg].visible = true;
            }
            else {
                map.removeLayer( LGS[lg].actor );
                LGS[lg].visible = false;
            }
        });
    });

}).on('click', '#actor-edit', function(){

    let region_id = $(this).data('region-id');
    document.location.href = `/edit/region?map=${map_alias}&id=${region_id}`;

}).on('click', '#actor-regions-toggle', function (el) {
    MapControls.toggle_Regions(this);
}).on('click', '#actor-viewbox-toggle', function (el) {
    MapControls.toggle_Info(this);
}).on('click', "#actor-backward-toggle", function (el) {
    MapControls.toggle_Backward(this);
}).on('change', "#sort-select", function(e){

    let must_display = (e.target.value === 'total') ? "#data-ordered-alphabet" : "#data-ordered-latest";
    let must_hide = (e.target.value === 'total') ? "#data-ordered-latest" : "#data-ordered-alphabet";
    $(must_hide).hide();
    $(must_display).show();

}).on('click', '.action-focus-at-region', function(){
    // клик на ссылке в списке регионов
    let id_region = $(this).data('region-id');
    console.log("current_infobox_region_id = " + current_infobox_region_id);

    onclick_FocusRegion(id_region);
    manageInfoBox('show', id_region);

    window.location.hash = MapManager.WLH_makeLink(id_region);
    return false;

}).on('click', '#actor-section-infobox-toggle', function(){

    manageInfoBox('hide', null);

}).escape(function(){

    manageInfoBox('hide', null);

});
