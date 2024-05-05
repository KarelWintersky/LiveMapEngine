let map;

/* ========================================= */
/* получается это FOLIO-карта */
$(function() {
    let _mapManager = window._mapManager;

    $(".leaflet-container").css('background-color', window.theMap['display']['background_color']);

    let options = {
        crs: L.CRS.Simple,
        minZoom: -3,
        maxZoom: 2,
        preferCanvas: true,
        renderer: L.canvas(),
        scrollWheelZoom: true,
        smoothWheelZoom: true,
        smoothSensitivity: 1,
        zoomControl: true,
    };

    let map = L.map('map', options);
    map.scrollWheelZoom.disable(); //@todo: нужен, если используется плавный зум! Иначе всё дергается
    map.setZoom( window.theMap['display']['zoom']);
    map.attributionControl.setPrefix(window.theMap.map.attribution || '');

    let regionsDataset = _mapManager.buildRegionsDataset();

    const current_bounds = [
        [0, 0],
        [window.theMap['map']['height'], window.theMap['map']['width']]
    ];
    map.fitBounds(current_bounds);

    let image = L.imageOverlay( window.theMap['map']['imagefile'], current_bounds);
    image.addTo(map);

    /*if (window.theMap['maxbounds']) {
        let mb = window.theMap['maxbounds'];
        map.setMaxBounds([
            [
                mb['topleft_h'] * window.theMap['map']['height'],
                mb['topleft_w'] * window.theMap['map']['width']
            ],
            [
                mb['bottomright_h'] * window.theMap['map']['height'],
                mb['bottomright_w'] * window.theMap['map']['width']
            ]
        ]);
    }*/

    let poly_layer = new L.LayerGroup();

    // draw polygons on map, bind on-click function
    Object.keys( regionsDataset ).forEach(function( id_region ) {
        poly_layer.addLayer(
            regionsDataset[ id_region ].on('click', function(){

                window.location.hash = MapManager.WLH_makeLink(id_region);

                let title = (window.theMap['regions'][id_region]['title'] != '')
                    ? window.theMap['regions'][id_region]['title']
                    : '';

                _mapManager.showContentColorBox(id_region, title);
            })
        );
    });

    poly_layer.addTo(map);

    MapControls.declareControl_Backward();

    // не показываем контрол "назад" если страница загружена в iframe
    if (! MapControls.isLoadedToIFrame()) {
        map.addControl( new L.Control.Backward() );
    }

    /*if (true) {
        const wlh_options = MapManager.WLH_getAction(polymap);

        map.fitBounds(current_bounds);

        if (wlh_options) {
            do_RegionShowInfo(wlh_options);
            do_RegionFocus(wlh_options, polymap);
        } else {
            map.fitBounds(current_bounds);
        }
    }*/

    // zoom control (а если сектора нет?)
    map.on('zoomend', function() {
        let currentZoom = map.getZoom();
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
    /*do_RegionFocus({
        action: 'focus',
        region_id: $(this).data('region-id')
    }, polymap);*/
}).on('click', '#actor-edit', function(){
    /*const region_id = $(this).data('region-id');
    document.location.href = '/edit/region?map=' + map_alias + '&id=' + region_id;*/
});
