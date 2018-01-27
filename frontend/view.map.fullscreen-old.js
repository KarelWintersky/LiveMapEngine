var current_infobox_region_id = '';
var map;
var polymap = Object.create(null);
var LGS = Object.create(null);
var fullpolymap = Object.create(null);

// map.scrollWheelZoom.disable();

$(function(){
    // умолчательные действия
    $(".leaflet-container").css('background-color', theMap['display']['background_color']);

    map = L.map('map', {
        crs: L.CRS.Simple,
        minZoom: theMap['display']['zoom_min'],
        maxZoom: theMap['display']['zoom_max'],
        preferCanvas: true,
        renderer: L.canvas(),
        zoomControl: false,
    });
    map.addControl(new L.Control.Zoomslider({position: 'bottomright'}));

    var current_bounds  = [ [0, 0], [theMap['map']['height'], theMap['map']['width'] ] ];

    var image = L.imageOverlay( theMap['map']['imagefile'], current_bounds).addTo(map);

    if (theMap['maxbounds']) {
        var mb = theMap['maxbounds'];
        map.setMaxBounds([ [ mb['topleft_h'] * theMap['map']['height'], mb['topleft_w'] * theMap['map']['width'] ]  , [ mb['bottomright_h'] * theMap['map']['height'], mb['bottomright_w'] * theMap['map']['width'] ] ]);
    }

    map.setZoom( theMap['display']['zoom'] );

    /* ================================================================================================ */
    /* A */
    Object.keys( theMap['layers'] ).forEach(function(id_layer){
        let regions_at_layer = buildRegionsAtLayer( theMap['layers'][id_layer] );

        Object.keys( regions_at_layer ).forEach(function(id_region){
            regions_at_layer[id_region].on('click', function(){
                window.location.hash = "#view=[" + id_layer + '|' + id_region + "]";
                toggleContentViewBox(id_region, id_layer);
            });
        });
        polymap[ id_layer ] = regions_at_layer;
    });

    Object.keys( polymap ).forEach(function(id_layer){
        let lg = new L.LayerGroup();

        Object.keys( polymap[id_layer] ).forEach(function(id_region){
            lg.addLayer( polymap[id_layer][id_region] );
        });

        if (map.getZoom().inbound( theMap['layers'][id_layer]['zoom_min'], theMap['layers'][id_layer]['zoom_max'] )) {
            map.addLayer(lg);
        } else {
        }

        LGS[id_layer] = lg;
    });

    /* ==================================================================================================== */

    createControl_RegionsBox();
    createControl_InfoBox();
    createControl_Backward();

    // не показываем контрол "назад" если страница загружена в iframe
    if (! (window != window.top || document != top.document || self.location != top.location)) {
        map.addControl( new L.Control.Backward() );
    }

    // показываем контентный регион только если есть список регионов с данными
    if (regions_with_content_ids.length) {
        map.addControl( new L.Control.RegionsBox() );
    }

    // его надо создавать только когда заявили показ информации!
    var __InfoBox = new L.Control.InfoBox();
    map.addControl( __InfoBox );

    if (true) {
        var wlh_options = wlhBased_GetActionWOL(polymap);
        if (wlh_options) {

            // map.fitBounds(current_bounds);
            do_RegionShowInfo(wlh_options);
            do_RegionFocus(wlh_options);
        } else {
            map.fitBounds(current_bounds);
        }
    }

    // zoom control (а если сектора нет?)
    map.on('zoomend', function() {
        var currentZoom = map.getZoom();
        console.log("Zoom: " + currentZoom);
        Object.keys( theMap['layers'] ).forEach(function(layer){
            var zmin = theMap['layers'][layer]['zoom_min'];
            var zmax = theMap['layers'][layer]['zoom_max'];

            // console.log("Current zoom: [" + currentZoom + "], Layer [" + layer + "] have zoom bounds [" + zmin + " .. " + zmax + "], visibility is " + currentZoom.inbound(zmin, zmax));

            if (currentZoom.inbound(zmin, zmax)) {
                console.log("+" + layer);
                map.addLayer( LGS[layer] );
            }
            else {
                console.log("-" + layer);
                map.removeLayer( LGS[layer] );
            }
        });
    });


}).on('click', '#actor-regions-toggle', function (el) {
        toggleRegionsBox(this);
    })
    .on('click', '#actor-viewbox-toggle', function (el) {
        toggleInfoBox(this);
    })
    .on('click', "#actor-backward-toggle", function (el) {
        toggle_BackwardBox(this);
    })
    .on('change', "#sort-select", function(e){
        var must_display = (e.target.value == 'total') ? "#data-ordered-alphabet" : "#data-ordered-latest";
        var must_hide = (e.target.value == 'total') ? "#data-ordered-latest" : "#data-ordered-alphabet";
        $(must_hide).hide();
        $(must_display).show();
    })
    .on('change', "#sort-select", function(e){
        var must_display = (e.target.value == 'total') ? "#data-ordered-alphabet" : "#data-ordered-latest";
        var must_hide = (e.target.value == 'total') ? "#data-ordered-latest" : "#data-ordered-alphabet";
        $(must_hide).hide();
        $(must_display).show();
    })
    .on('click', '.action-focus-at-region', function(){
        // клик на ссылке в списке регионов
        var id_region = $(this).data('region-id');
        var id_layer = find_RegionInLayers(id_region);

        console.log('Сделан клик на ссылке в списке регионов');
        console.log("Запрошен регион: " + id_region + " принадлежащий слою: " + id_layer );

        do_RegionFocus({
            action: 'focus',
            layer: id_layer,
            id_region: id_region
        }, polymap);

        window.location.hash = "#focus=[" + id_layer + '|' + id_region + "]";
        return false;
    })
    .on('click', '#actor-edit', function(){
        var region_id = $(this).data('region-id');
        document.location.href = '/edit/region?map=' + map_alias + '&id=' + region_id;
    });
