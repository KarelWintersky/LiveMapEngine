polymap = buildPolymap(theMap);

var map = L.map('map', {
    crs: L.CRS.Simple,
    minZoom: -3,
    maxZoom: 2,
    preferCanvas: true,
    renderer: L.canvas(),
    zoomControl: false,
});

map.attributionControl.setPrefix('');
map.scrollWheelZoom.disable();

var current_bounds  = [ [0, 0], [theMap['map']['height'], theMap['map']['width'] ] ];
var image = L.imageOverlay( theMap['map']['imagefile'], current_bounds).addTo(map);

if (theMap['maxbounds']) {
    var mb = theMap['maxbounds'];
    map.setMaxBounds([ [ mb['topleft_h'] * theMap['map']['height'], mb['topleft_w'] * theMap['map']['width'] ]  , [ mb['bottomright_h'] * theMap['map']['height'], mb['bottomright_w'] * theMap['map']['width'] ] ]);
}

// draw polygons on map, bind on-click function
Object.keys( polymap ).forEach(function(id_region){
    polymap[ id_region ].addTo(map).on('click', function(){
        window.location.hash = 'view=[' + id_region + ']';
        var t = (theMap['regions'][ id_region ]['title'] != '')
            ? theMap['regions'][ id_region ]['title']
            : '';

        showContentColorbox(id_region, t);
    });
});

map.fitBounds(current_bounds);
map.setZoom( theMap['display']['zoom'] );

// обрабатываем Window Location Hash
if (true) {
    var wlh_options = wlhBased_GetAction(polymap);
    if (wlh_options) {
        do_RegionShowInfo(wlh_options);
        do_RegionFocus(wlh_options, polymap);
    } else {
        map.fitBounds(current_bounds);
    }
}
map.setZoom( theMap['map']['zoom'] );

createControl_Backward();

$(function(){
    $(".leaflet-container").css('background-color', theMap['display']['background_color']);

    // создаем контролы
    // не показываем контрол "назад" если страница загружена в iframe
    if (! (window != window.top || document != top.document || self.location != top.location)) {
        var __BackwardBox = new L.Control.Backward();
        map.addControl( __BackwardBox );
    }

    // закрашиваем регионы с информацией другим цветом
    //@todo: use fillColor from DB ( polymap[key]['present_region_fillcolor']. Сейчас используется дефолтное значение, причем хардкод :(
    // причем нужно использовать значение из элемента, если оно отсутствует - то слоя, если отсутвтвует - то дефолт.
    regions_with_content.forEach(function(key){
        if (key in polymap) {
            polymap[ key ].setStyle({fillColor: '#00ff00'});
        }
    });


    $("#actor-backward-toggle").on('click', function (el){
        var state = $(this).data('content-is-visible');
        var text = (state == false) ? '&lt;' : '&gt;';
        $(this).html(text);

        var data = $(this).data('content');
        $('#' + data).toggle();
        $(this).data('content-is-visible', !state);
    });

});

// клик по региону в списке "интересных мест", созданном контролом createControl_RegionsBox()
// для красоты .on() можно прикрепить к $(function() {}) выше.
$(document).on('click', '.action-focus-at-region', function(){
    do_RegionFocus({
        action: 'focus',
        region_id: $(this).data('region-id')
    }, polymap);
});

// id="bind-actor-edit"
$(document).on('click', '#actor-edit', function(){
    var region_id = $(this).data('region-id');
    document.location.href = '/edit/region?map=' + map_alias + '&id=' + region_id;
});