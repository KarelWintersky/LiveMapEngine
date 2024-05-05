let current_infobox_region_id;
let polymap;

/* ========================================= */
$(function(){
    $(".leaflet-container").css('background-color', theMap['display']['background_color']);

    polymap = buildPolymap(theMap);

    const map = L.map('map', {
        crs: L.CRS.Simple,
        minZoom: -3,
        maxZoom: 2,
        preferCanvas: true,
        renderer: L.canvas(),
        zoomControl: false,
    });
    map.attributionControl.setPrefix('');
    map.scrollWheelZoom.disable();

    map.addControl(new L.Control.Zoomslider({position: 'bottomright'}));

    const current_bounds = [[0, 0], [theMap['map']['height'], theMap['map']['width']]];

    const image = L.imageOverlay(theMap['map']['imagefile'], current_bounds).addTo(map);

    if (theMap['maxbounds']) {
        var mb = theMap['maxbounds'];
        map.setMaxBounds([ [ mb['topleft_h'] * theMap['map']['height'], mb['topleft_w'] * theMap['map']['width'] ]  , [ mb['bottomright_h'] * theMap['map']['height'], mb['bottomright_w'] * theMap['map']['width'] ] ]);
    }
    map.setZoom( theMap['display']['zoom'] );

    const poly_layer = new L.LayerGroup();

    // draw polygons on map, bind on-click function
    Object.keys( polymap ).forEach(function( id_region ) {
        poly_layer.addLayer(
            polymap[ id_region ].on('click', function(){

                // window.location.hash = MapManager.WLH_makeLink(id_region);

                window.location.hash = 'view=[' + id_region + ']';
                const t = (theMap['regions'][id_region]['title'] != '')
                    ? theMap['regions'][id_region]['title']
                    : '';

                showContentColorbox(id_region, t);
            })
        );
    });

    poly_layer.addTo(map);

    MapControls.declareControl_Backward();

    // не показываем контрол "назад" если страница загружена в iframe
    if (! (window != window.top || document != top.document || self.location != top.location)) {
        map.addControl( new L.Control.Backward() );
    }

    if (true) {
        const wlh_options = MapManager.WLH_getAction(polymap);

        map.fitBounds(current_bounds);

        if (wlh_options) {
            do_RegionShowInfo(wlh_options);
            do_RegionFocus(wlh_options, polymap);
        } else {
            map.fitBounds(current_bounds);
        }
    }

    // zoom control (а если сектора нет?)
    map.on('zoomend', function() {
        var currentZoom = map.getZoom();
        console.log("Current zoom: " + currentZoom);
        /*if (sector == null) return;

         if (currentZoom < sector_options.zoom_threshold) {
         group.clearLayers();
         // map.removeLayer(sector);
         } else {
         group.addLayer( sector );
         // map.addLayer(sector);
         }*/
    });


}).on('click', "#actor-backward-toggle", function (el){
    const state = $(this).data('content-is-visible');
    const text = (state == false) ? '&lt;' : '&gt;';
    $(this).html(text);

    const data = $(this).data('content');
    $('#' + data).toggle();
    $(this).data('content-is-visible', !state);
}).on('click', '.action-focus-at-region', function(){
    do_RegionFocus({
        action: 'focus',
        region_id: $(this).data('region-id')
    }, polymap);
}).on('click', '#actor-edit', function(){
    const region_id = $(this).data('region-id');
    document.location.href = '/edit/region?map=' + map_alias + '&id=' + region_id;
});
