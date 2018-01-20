var current_infobox_region_id = '';

polymap = buildPolymap(theMap);

//@todo: различные варианты CRS и исходных данных тайлов в зависимости от конфига
var map = L.map('map', {
    crs: L.CRS.Simple,
    minZoom: -3,
    maxZoom: 2,
    preferCanvas: true,
    renderer: L.canvas(),
    zoomControl: false,
});

createControl_RegionsBox();
createControl_InfoBox();
createControl_Backward();

//@todo: придумать, как объединить в одном контроле кнопки +/- и кнопку backward:
/*
+
   <  (Назад на карту)
-
 */
L.control.zoom({ position: 'bottomleft' }).addTo(map);

var h = theMap['map']['height'];
var w = theMap['map']['width'];
var current_bounds  = [ [0, 0], [h, w ] ];

var image = L.imageOverlay( theMap['map']['imagefile'], current_bounds).addTo(map);

if (theMap['maxbounds']) {
    var mb = theMap['maxbounds'];
    map.setMaxBounds([ [ mb['topleft_h'] * h, mb['topleft_w'] * w ]  , [ mb['bottomright_h'] * h, mb['bottomright_w'] * w ] ]);
}

var poly_layer = new L.LayerGroup();
    poly_layer.addTo(map);

// draw polygons on map, bind on-click function
Object.keys( polymap ).forEach(function( id_region ) {
    poly_layer.addLayer(
        polymap[ id_region ].on('click', function(){

            window.location.hash = 'view=[' + id_region + ']';
            var t = (theMap['regions'][ id_region ]['title'] != '')
                ? theMap['regions'][ id_region ]['title']
                : '';

            toggleContentViewBox(id_region, t);
        })
    );
});


// обрабатываем Window Location Hash
if (true) {
    var wlh_options = wlhBased_GetAction(polymap);
    if (wlh_options) {
        wlhBased_RegionShowInfo(wlh_options);
        wlhBased_RegionFocus(wlh_options, polymap);
    } else {
        map.fitBounds(current_bounds);
    }
}

map.setZoom( theMap['map']['zoom'] );

// основные функции
$(function(){
    // умолчательные действия
    $(".leaflet-container").css('background-color', leaflet_background_color);

    // создаем контролы

    // не показываем контрол "назад" если страница загружена в iframe
    if (! (window != window.top || document != top.document || self.location != top.location)) {
        var __BackwardBox = new L.Control.Backward();
        map.addControl( __BackwardBox );
    }

    // показываем контентный регион только если есть список регионов с данными
    if (regions_with_content.length) {
        var __RegionsBox = new L.Control.RegionsBox();
        map.addControl( __RegionsBox );
    }

    // его надо создавать только когда заявили показ информации!
    var __InfoBox = new L.Control.InfoBox();
    map.addControl( __InfoBox );


    // закрашиваем регионы с информацией другим цветом
    //@todo: use fillColor from DB ( polymap[key]['present_region_fillcolor']. Сейчас используется дефолтное значение, причем хардкод :(
    // причем нужно использовать значение из элемента, если оно отсутствует - то слоя, если отсутвтвует - то дефолт.
    regions_with_content.forEach(function(key){
        if (key in polymap) {
            polymap[ key ].setStyle({fillColor: '#00ff00'});
        }
    });


    // toggle блоков с информацией/регионами
    //@todo: внести это правило внутрь метода создания контрола?
    $('#actor-regions-toggle').on('click', function (el) {
        toggleRegionsBox(this);
    });

    //@todo: внести это правило внутрь метода создания контрола?
    $('#actor-viewbox-toggle').on('click', function (el) {
        toggleInfoBox(this);
    });

    //@todo: внести это правило внутрь метода создания контрола?
    $("#actor-backward-toggle").on('click', function (el){
        var state = $(this).data('content-is-visible');
        var text = (state == false) ? '&lt;' : '&gt;'; //@todo: сообщения на активном/свернутом виде перенести в дата-атрибуты
        $(this).html(text);

        var data = $(this).data('content');
        $('#' + data).toggle();
        $(this).data('content-is-visible', !state);
    });

    // изменение контента блока с регионами на основе типа сортировки
    $("#sort-select").on('change', function(e){
        var must_display = (e.target.value == 'total') ? "#data-ordered-alphabet" : "#data-ordered-latest";
        var must_hide = (e.target.value == 'total') ? "#data-ordered-latest" : "#data-ordered-alphabet";
        $(must_hide).hide();
        $(must_display).show();
    });

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


});

// клик по региону в списке "интересных мест", созданном контролом createControl_RegionsBox()
// для красоты .on() можно прикрепить к $(function() {}) выше.
$(document).on('click', '.action-focus-at-region', function(){
    wlhBased_RegionFocus({
        action: 'focus',
        region_id: $(this).data('region-id')
    }, polymap);
});

// id="bind-actor-edit"
$(document).on('click', '#actor-edit', function(){
    var region_id = $(this).data('region-id');
    document.location.href = '/edit/region?map=' + map_alias + '&id=' + region_id;
});
