showContentColorbox = function(id_region , title) {
    var url = '/api/get/regiondata?map=' + map_alias + '&id=' + id_region + is_iframe;
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
var is_iframe = ((window != window.top || document != top.document || self.location != top.location)) ? '&resultType=iframe' : '';

// build polygons
Object.keys( theMap.regions ).forEach(function( key ){
    var region = theMap.regions[ key ];
    var type = region['type'];
    var coords = region['coords'];
    var options = {
        color: region['color']      ||  theMap.defaults.polygon_color,
        width: region['width']      ||  theMap.defaults.polygon_width,
        opacity: region['opacity']    ||  theMap.defaults.polygon_opacity,
        fillColor: region['fillColor']  ||  theMap.defaults.polygon_fillColor,
        fillOpacity: region['fillOpacity'] || theMap.defaults.polygon_fillOpacity,
        radius: region['radius'] || 0
    };

    var entity;
    if (type == 'polygon') {
        entity = L.polygon(coords, options);
    } else if (type == 'rect') {
        entity = L.rectangle(coords, options);
    } else if (type == 'circle') {
        entity = L.circle(coords, options)
    }

    polymap[ key ] = entity;
} );

var map = L.map('map', {
    crs: L.CRS.Simple,
    minZoom: -3,
    maxZoom: 2,
    preferCanvas: true,
    renderer: L.canvas(),
});
map.attributionControl.setPrefix('');
map.scrollWheelZoom.disable();

var h = theMap['map']['height'];
var w = theMap['map']['width'];
var current_bounds  = [ [0, 0], [h, w ] ];
var image = L.imageOverlay( theMap['map']['imagefile'], current_bounds).addTo(map);

if (theMap['maxbounds']) {
    var mb = theMap['maxbounds'];
    map.setMaxBounds([ [ mb['topleft_h'] * h, mb['topleft_w'] * w ]  , [ mb['bottomright_h'] * h, mb['bottomright_w'] * w ] ]);
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
    var region_id = $(this).data('region-id');
    document.location.href = '/edit/region?map='+ map_alias + '&id=' + region_id;
});

