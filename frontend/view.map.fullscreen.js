var focus_animate_duration = theMap['display']['focus_animate_duration'] || 0.7;
var focus_highlight_color = theMap['display']['focus_highlight_color'] || '#ff0000';
var focus_timeout = theMap['display']['focus_timeout'] || 1500;
var current_infobox_region_id = '';
var map;
var base_map_bounds;
var __InfoBox = null;
var IS_DEBUG = false;
var LGS = Object.create(null);
var polymap = Object.create(null);


;$(function(){
    $(".leaflet-container").css('background-color', theMap['display']['background_color']);

    map = setup_MapCreate('map', theMap);

    base_map_bounds = setup_MapSetMaxBounds(map, theMap);

    var image = setup_MapCreateOverlay(map, theMap, base_map_bounds);

    map.setZoom( theMap['display']['zoom'] );

    // строим массив всех регионов
    polymap = buildPolymap( theMap );

    // биндим к каждому объекту функцию, показывающую информацию
    Object.keys( polymap ).forEach(function(id_region){
        polymap[id_region].on('click', function(){
            // обернуть в функцию и отрефакторить
            if (current_infobox_region_id == id_region) {
                manageInfoBox('toggle', id_region);
            } else {
                manageInfoBox('show', id_region);
            }
            current_infobox_region_id = id_region;

        });
    });

    // раскладываем регионы по layer-группам
    Object.keys( polymap ).forEach(function(id_region){
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
        LGS[id_layer].actor.addLayer( polymap[id_region] );
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

    createControl_RegionsBox();
    createControl_InfoBox();
    createControl_Backward();

    // не показываем контрол "назад" если страница загружена в iframe
    if (! (window != window.top || document != top.document || self.location != top.location)) {
        map.addControl( new L.Control.Backward() );
    }

    // показываем список регионов только если он не пуст
    if (regions_with_content_ids.length) {
        map.addControl( new L.Control.RegionsBox() );
    }

    // анализируем window.location.hash
    if (true) {
        var wlh_options = wlhBased_GetAction(polymap);
        if (wlh_options) {
            // было бы более интересным решением имитировать триггером клик по ссылке на регионе, но.. оно не работает
            // $("a.action-focus-at-region[data-region-id='" + wlh_options.id_region + "']").trigger('click');

            wlh_FocusRegion(wlh_options.id_region);
            manageInfoBox('show', wlh_options.id_region);
        } else {
        }
    }

    // отлавливаем зум
    map.on('zoomend', function() {
        var currentZoom = map.getZoom();
        if (IS_DEBUG) console.log("zoom at zoomend -> " + currentZoom);

        Object.keys( LGS ).forEach(function(lg){
            var zmin = LGS[lg].zoom_min;
            var zmax = LGS[lg].zoom_max;

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

    var region_id = $(this).data('region-id');
    document.location.href = '/edit/region?map=' + map_alias + '&id=' + region_id;

}).on('click', '#actor-regions-toggle', function (el) {

    toggleRegionsBox(this);

}).on('click', '#actor-viewbox-toggle', function (el) {

    toggleInfoBox(this);

}).on('click', "#actor-backward-toggle", function (el) {

    toggle_BackwardBox(this);

}).on('change', "#sort-select", function(e){

    var must_display = (e.target.value == 'total') ? "#data-ordered-alphabet" : "#data-ordered-latest";
    var must_hide = (e.target.value == 'total') ? "#data-ordered-latest" : "#data-ordered-alphabet";
    $(must_hide).hide();
    $(must_display).show();

}).on('change', "#sort-select", function(e){

    var must_display = (e.target.value == 'total') ? "#data-ordered-alphabet" : "#data-ordered-latest";
    var must_hide = (e.target.value == 'total') ? "#data-ordered-latest" : "#data-ordered-alphabet";
    $(must_hide).hide();
    $(must_display).show();

}).on('click', '.action-focus-at-region', function(){
    // клик на ссылке в списке регионов
    var id_region = $(this).data('region-id');
    if (IS_DEBUG) console.log("CLICK action-focus-at-region' -> " + id_region);
    console.log("current_infobox_region_id = " + current_infobox_region_id);

    onclick_FocusRegion(id_region);
    manageInfoBox('show', id_region);

    window.location.hash = "#view=[" + id_region + "]";
    return false;

}).on('click', '#actor-section-infobox-toggle', function(){

    manageInfoBox('hide', null);

}).escape(function(){

    manageInfoBox('hide', null);

});
