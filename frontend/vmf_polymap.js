var current_infobox_region_id = '';
var map;
var LGS = Object.create(null);
var polymap = Object.create(null);
var _prevent = false;
var base_map_bounds;

var LGDef = Object.create(null);

$(function(){
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
    base_map_bounds  = [ [0, 0], [theMap['map']['height'], theMap['map']['width'] ] ];
    var image = L.imageOverlay( theMap['map']['imagefile'], base_map_bounds).addTo(map);
    if (theMap['maxbounds']) {
        var mb = theMap['maxbounds'];
        map.setMaxBounds([ [ mb['topleft_h'] * theMap['map']['height'], mb['topleft_w'] * theMap['map']['width'] ]  , [ mb['bottomright_h'] * theMap['map']['height'], mb['bottomright_w'] * theMap['map']['width'] ] ]);
    }
    map.setZoom( theMap['display']['zoom'] );

    polymap = buildPolymap( theMap );

    Object.keys( polymap ).forEach(function(id_region){
        let id_layer = theMap['regions'][id_region]['layer'];

        if (!(id_layer in LGS)) {
            let lg = new L.LayerGroup();
            LGS[ id_layer ] = {
                actor: lg,
                visible: false,
                zoom: theMap['layers'][id_layer]['zoom'],
                zoom_min: theMap['layers'][id_layer]['zoom_min'],
                zoom_max: theMap['layers'][id_layer]['zoom_max'],
            };
        }

        polymap[id_region].on('click', function(){
            direct_ShowRegionInfo(id_region);
        });

        LGS[id_layer].actor.addLayer( polymap[id_region] );
    });

    Object.keys( LGS ).forEach(function(lg){
        if (map.getZoom().inbound( theMap['layers'][lg]['zoom_min'], theMap['layers'][lg]['zoom_max'] )) {
            map.addLayer( LGS[lg].actor );
            LGS[lg].visible = true;
        } else {
            map.addLayer( LGS[lg].actor );
            LGS[lg].actor.remove();
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

    if (regions_with_content_ids.length) {
        map.addControl( new L.Control.RegionsBox() );
    }

    if (true) {
        var wlh_options = wlhBased_GetActionWOL(polymap);
        if (wlh_options) {
            wlh_ShowRegionInfo(wlh_options.id_region);

            // $("a.action-focus-at-region[data-region-id='" + wlh_options.id_region + "']").trigger('click');



            // map.fitBounds(base_map_bounds);
            // do_RegionShowInfo(wlh_options);
            // do_RegionFocus(wlh_options);
        } else {
            // map.fitBounds(base_map_bounds);
        }
    }



    map.on('zoomend', function() {
        var currentZoom = map.getZoom();
        console.log("zoom at zoomend -> " + currentZoom);

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

}).on('click', '#actor-regions-toggle', function (el) {
    toggleRegionsBox(this);
}).on('click', '.action-focus-at-region', function(){
    // клик на ссылке в списке регионов
    var id_region = $(this).data('region-id');
    console.log("'click', '.action-focus-at-region' -> " + id_region);

    direct_FocusRegion(id_region);
    direct_ShowRegionInfo(id_region);

    window.location.hash = "#focus=[" + id_region + "]";
    return false;
});

/* ====================================================================== */

direct_ShowRegionInfo = function(id_region){
    console.log(id_region);
};

direct_FocusRegion = function(id_region){
    var id_layer = theMap['regions'][id_region]['layer'];
    var is_visible = LGS[id_layer].visible;
    var bounds;

    console.log("direct_FocusRegion -> layer " + id_layer + " is_visible " + is_visible);
    console.log( LGS[id_layer].actor );

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

wlh_ShowRegionInfo = function(id_region){
    /* позиционируем */

    var id_layer = theMap['regions'][id_region]['layer'];
    var is_visible = LGS[id_layer].visible;
    var bounds;

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
    map.setZoom( theMap['layers'][id_layer]['zoom'], {
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

};

wlh_FocusRegion = function(id_region){

}

wlhBased_GetActionWOL = function(polymap) {
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
