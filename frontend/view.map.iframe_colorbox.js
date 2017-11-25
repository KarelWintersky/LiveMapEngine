showContentColorbox = function(id_region , title) {
    let url = '/api/get/regiondata?map=' + map_alias + '&id=' + id_region;
    $.get( url, function() {
    }).done(function(data) {
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

var polymap = Object.create(null);

// build polygons
Object.keys( theMap.regions ).forEach(function( key ){
    var path = theMap.regions[ key ]['path'];
    var polygon_color   =   theMap.regions[ key ]['color']      ||  theMap.defaults.polygon_color;
    var polygon_width   =   theMap.regions[ key ]['width']      ||  theMap.defaults.polygon_width;
    var polygon_opacity =   theMap.regions[ key ]['opacity']    ||  theMap.defaults.polygon_opacity;
    var polygon_fillcolor = theMap.regions[ key ]['fillColor']  ||  theMap.defaults.polygon_fillColor;
    var polygon_fillopacity = theMap.regions[ key ]['fillOpacity'] || theMap.defaults.polygon_fillOpacity;

    polymap[ key ] = L.polygon( path, {
        color: polygon_color,
        width: polygon_width,
        opacity: polygon_opacity,
        fillColor: polygon_fillcolor,
        fillOpacity: polygon_fillopacity
    });
} );

var map = L.map('map', {
    crs: L.CRS.Simple,
    minZoom: -3,
    maxZoom: 2,
    preferCanvas: true,
    renderer: L.canvas(),
});
map.attributionControl.setPrefix('');

var h = theMap['map']['height'];
var w = theMap['map']['width'];
var current_bounds  = [ [0, 0], [h-1, w-1 ] ];
var max_bounds      = [ [-h*0.5, -w*0.5], [h*1.5 , w*1.5 ] ];

var image = L.imageOverlay( theMap['map']['imagefile'], current_bounds).addTo(map);

map.setMaxBounds(max_bounds);

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
map.setZoom( theMap['map']['zoom']);

// bind-action-focus-region
// при получении параметров на старте:
// view= - показываем попап
// focus= = делаем центровку на регионе
var wlh = window.location.hash;
if (wlh.length > 1) {
    var hashparams = wlh.match(/(view|focus)=\[(.*)\]/);
    var id_region = '';

    if ((hashparams !== null) && (hashparams[1] == 'view')) {
        id_region = hashparams[2];
        showContentColorbox(id_region, '');

    } else if ((hashparams !== null) && (hashparams[1] == 'focus')) {
        id_region = hashparams[2];
        // focus
        var hash_region_bounds = polymap [ id_region ].getBounds();
        map.panTo( hash_region_bounds.getCenter(), { animate: true, duration: 0.5, noMoveStart: true} );
        var oldstyle = polymap[ id_region ].options['fillColor'];

        polymap[ id_region ].setStyle({fillColor: '#ff0000'});
        var timeoutHandler = setInterval(function(){
            polymap[ id_region ].setStyle({fillColor: oldstyle});
            window.clearTimeout(timeoutHandler);
        }, 1000);
        // history.pushState('', document.title, window.location.pathname);
    }
} else {
    map.fitBounds(current_bounds);
}

map.setZoom( theMap['map']['zoom'] );


// создаем контролы
L.Control.Backward = L.Control.extend({
    options: {
        position: 'bottomleft'
    },
    onAdd: function(map) {
        var div = L.DomUtil.get('section-backward');
        L.DomUtil.removeClass(div, 'invisible');
        L.DomEvent.disableScrollPropagation(div);
        L.DomEvent.disableClickPropagation(div);
        return div;
    },
    onRemove: function(map){}
});

$(function(){
    $(".leaflet-container").css('background-color', leaflet_background_color);

    // закрашиваем регионы с информацией другим цветом
    regions_with_content.forEach(function(key){
        polymap[ key ].setStyle({fillColor: '#00ff00'});
    });

    // не показываем контрол "назад" если страница загружена в iframe
    if (!(window != window.top || document != top.document || self.location != top.location)) {
        var __BackwardBox = new L.Control.Backward();
        map.addControl( __BackwardBox );
    }

    $("#actor-backward-toggle").on('click', function (el){
        var state = $(this).data('content-is-visible');
        var text = (state == false) ? '&lt;' : '&gt;';
        $(this).html(text);

        var data = $(this).data('content');
        $('#' + data).toggle();
        $(this).data('content-is-visible', !state);
    });

});

// обрабатываем клик по ссылке внутри попап окна
// (на самом деле надо проверять, это ссылка на ту же карту или нет?)
$(document).on('click', '#cboxLoadedContent a', function(){
    var href = $(this).attr('href');
    var wlh = window.location.href;

    if (href.indexOf( '#view' ) == 0) { // если href содержит ссылку на popup с информацией...
        var href_params = href.match(/view=\[(.*)\]/);
        if (href_params != null) {
            history.pushState('', document.title, window.location.pathname + href);
            showContentColorbox(href_params[1], '');
        }
    } else {
        window.location.assign(href);
        window.location.reload(true);
    }

    return false;
});

$(document).on('click', '#actor-edit', function(){
    let region_id = $(this).data('region-id');
    document.location.href = '/edit/region?map='+ map_alias + '&id=' + region_id;
});

