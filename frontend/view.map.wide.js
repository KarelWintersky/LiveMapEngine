var current_infobox_region_id = '';

showContentColorbox = function(id_region , title) {
    var url = '/api/get/regiondata?map=' + map_alias + '&id=' + id_region;
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

toggleContentViewBox = function(id_region, title) {
    var url = '/api/get/regiondata?map=' + map_alias + '&id=' + id_region;
    if (current_infobox_region_id == id_region) {
        toggleInfoBox('#actor-viewbox-toggle');

        if ( $('#actor-viewbox-toggle').data('content-is-visible') == false ) {
            history.pushState('', document.title, window.location.pathname);
        } else {
            window.location.hash = "#view=[" + id_region + "]";
        }

    } else {
        current_infobox_region_id = id_region;
        $("#section-info-content").html('');
        document.getElementById('section-info-content').scrollTop = 0; // scroll box to top

        $.ajax({
            url: url,
            type: 'GET',
            async: false
        }).done(function(data){
            var region_center = polymap [ id_region ].getBounds().getCenter();

            // сдвиг происходит только если регион слишком близко к центру (ближе 70 пикселей)
            if (map_centring_panning_step > 0) {
                if (region_center.lng > map.getBounds().getCenter().lng ) {
                    region_center.lng += map_centring_panning_step;
                    map.panTo( region_center, { animate: true, duration: 0.5, noMoveStart: true} );
                }
            } else {
                if (region_center.lng <= map.getBounds().getCenter().lng ) {
                    region_center.lng += map_centring_panning_step;
                    map.panTo( region_center, { animate: true, duration: 0.5, noMoveStart: true} );
                }
            }

            $("#actor-viewbox-toggle").data('content-is-visible', true).html("Скрыть");
            $("#section-info-content").html(data).show();

        });
    }
}
showContentViewBox = function(id_region, title) {
    var url = '/api/get/regiondata?map=' + map_alias + '&id=' + id_region;

    $.get(url, function(){}).done(function(data){
        var region_center = polymap [ id_region ].getBounds().getCenter();
        region_center.lng -= 50; // move center to right (50px)
        map.panTo( region_center, { animate: true, duration: 0.5, noMoveStart: true} );
        $("#section-info-content").html(data).show();
        $("#actor-viewbox-toggle").data('content-is-visible', true).html("Скрыть");
    });
}
toggleRegionsBox = function(el) {
    var state = $(el).data('content-is-visible');
    var text = (state == false) ? '&nbsp;Скрыть&nbsp;' : 'Показать';
    $(el).html(text);

    var data = $(el).data('content');
    $('#' + data).toggle();
    $('#sort-select').toggle();
    $(el).data('content-is-visible', !state);
};
toggleInfoBox = function(el) {
    var state = $(el).data('content-is-visible');
    var text = (state == false) ? '&nbsp;Скрыть&nbsp;' : 'Показать';
    $(el).html(text);

    var data = $(el).data('content');
    $('#' + data).toggle();
    $(el).data('content-is-visible', !state);
}

var polymap = Object.create(null);
// build poligons
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
    zoomControl: false,
});
L.control.zoom({ position: 'bottomright' }).addTo(map);

var h = theMap['map']['height'];
var w = theMap['map']['width'];
var current_bounds  = [ [0, 0], [h-1, w-1 ] ];
var max_bounds      = [ [-h*0.5, -w], [h*1.5 , w*2 ] ];

var image = L.imageOverlay( theMap['map']['imagefile'], current_bounds).addTo(map);

map.setMaxBounds(max_bounds);

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
        showContentViewBox(id_region, '');

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
L.Control.RegionsBox = L.Control.extend({
    is_content_visible: false,
    options: {
        position: $("#section-regions").data('leaflet-control-position')
    },
    onAdd: function(map) {
        var div = L.DomUtil.get('section-regions');
        L.DomUtil.removeClass(div, 'invisible');
        L.DomUtil.enableTextSelection();
        L.DomEvent.disableScrollPropagation(div);
        L.DomEvent.disableClickPropagation(div);
        return div;
    },
    onRemove: function(map) {}
});
L.Control.InfoBox = L.Control.extend({
    is_content_visible: false,
    options: {
        position: $("#section-infobox").data('leaflet-control-position')
    },
    onAdd: function(map) {
        var div = L.DomUtil.get('section-infobox');
        L.DomUtil.removeClass(div, 'invisible');
        L.DomUtil.enableTextSelection();
        L.DomEvent.disableScrollPropagation(div);
        L.DomEvent.disableClickPropagation(div);
        return div;
    },
    onRemove: function(map) {}
});
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
        polymap[ key ].setStyle({fillColor: '#00ff00'});  // theMap[]['filled_region_color'] или цветом из информации о регионе
    });

    // не показываем контрол "назад" если страница загружена в iframe
    if (!(window != window.top || document != top.document || self.location != top.location)) {
        var __BackwardBox = new L.Control.Backward();
        map.addControl( __BackwardBox );
    }

    if (regions_with_content.length) {
        var __RegionsBox = new L.Control.RegionsBox();
        map.addControl( __RegionsBox );
    }

    // его надо создавать только когда заявили показ информации!
    var __InfoBox = new L.Control.InfoBox();
    map.addControl( __InfoBox );

    // toggle блоков с информацией/регионами
    $('#actor-regions-toggle').on('click', function (el) {
        toggleRegionsBox(this);
    });

    $('#actor-viewbox-toggle').on('click', function (el) {
        toggleInfoBox(this);
    });

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

// клик по региону в списке "интересных мест"
$(document).on('click', '.action-focus-at-region', function(){
    var id_region = $(this).data('region-id');

    var bound = polymap [ id_region ].getBounds();
    map.panTo( bound.getCenter(), { animate: true, duration: 0.7, noMoveStart: true});

    var oldstyle = polymap[ id_region ].options['fillColor'];

    polymap[ id_region ].setStyle({fillColor: '#ff0000'});

    setTimeout(function(){
        polymap[ id_region ].setStyle({fillColor: oldstyle});
        //когда сделаем кнопку, дающую ссылку на регион - эту строчку раскомментируем
        // history.pushState('', document.title, window.location.pathname);
    }, 1200);

});

// id="bind-actor-click-inside-colorbox"
// обрабатываем клик по ссылке внутри попап окна
// (на самом деле надо проверять, это ссылка на ту же карту или нет?)
//@todo: протестировать, отладить!
$(document).on('click', '#cboxLoadedContent a', function(){ // здесь другой элемент ловит событие!
    var href = $(this).attr('href');
    var wlh = window.location.href;

    if (href.indexOf( '#view' ) == 0) { // если href содержит ссылку на блок с информацией...
        var href_params = href.match(/view=\[(.*)\]/);
        if (href_params != null) {
            history.pushState('', document.title, window.location.pathname + href);

            showContentBox(href_params[1], '');
        }
    } else {
        window.location.assign(href);
        window.location.reload(true);
    }

    return false;
});

// id="bind-actor-edit"
$(document).on('click', '#actor-edit', function(){
    var region_id = $(this).data('region-id');
    document.location.href = '/edit/region?map=' + map_alias + '&id=' + region_id;
});
