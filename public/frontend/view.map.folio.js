$(function(){
    $(".leaflet-container").css('background-color', theMap['display']['background_color']);

    var polymap = buildPolymap(theMap);

    var map = L.map('map', {
        crs: L.CRS.Simple,
        minZoom: -3,
        maxZoom: 2,
        preferCanvas: true,
        zoomControl: false,
    });
    map.addControl(new L.Control.Zoomslider({position: 'bottomright'}));

    var current_bounds  = [ [0, 0], [theMap['map']['height'], theMap['map']['width'] ] ];
    var image = L.imageOverlay( theMap['map']['imagefile'], current_bounds).addTo(map);

    // max bounds
    if (theMap['maxbounds']) {
        var mb = theMap['maxbounds'];
        map.setMaxBounds([ [ mb['topleft_h'] * theMap['map']['height'], mb['topleft_w'] * theMap['map']['width'] ]  , [ mb['bottomright_h'] * theMap['map']['height'], mb['bottomright_w'] * theMap['map']['width'] ] ]);
    }

    // draw polygons on map, bind on-click function
    Object.keys( polymap ).forEach(function(id_region){
        polymap[ id_region ].addTo(map).on('click', function(){
            var t = theMap['regions'][ id_region ]['title'] || id_region;
            $("#section-region-title-content").html(t);
        });
    });

    map.fitBounds(current_bounds);
    map.setZoom( theMap['display']['zoom'] );

    // не показываем контрол "назад" если страница загружена в iframe
    if (! (window != window.top || document != top.document || self.location != top.location)) {
        MapControls.declareControl_Backward();
        map.addControl( new L.Control.Backward() );
    }

    MapControls.declareControl_RegionTitle();
    map.addControl( new L.Control.Title() );


    $("#actor-backward-toggle").on('click', function (el){
        var state = $(this).data('content-is-visible');
        var text = (state == false) ? '&lt;' : '&gt;';
        $(this).html(text);

        var data = $(this).data('content');
        $('#' + data).toggle();
        $(this).data('content-is-visible', !state);
    });

    map.on('click', function(ev) {
        // console.log(ev); // ev is an event object (MouseEvent in this case)
    });

    map.on('zoomend', function(ev) {
        // console.log( map.getZoom() );
    });

}).on('click', "#actor-backward-toggle", function (el){
    var state = $(this).data('content-is-visible');
    var text = (state == false) ? '&lt;' : '&gt;';
    $(this).html(text);

    var data = $(this).data('content');
    $('#' + data).toggle();
    $(this).data('content-is-visible', !state);
});

