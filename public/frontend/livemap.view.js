const URL_GETREGIONCONTENT = '/api/get/regiondata?map=';

Number.prototype.between = function(a, b) {
    let min = Math.min.apply(Math, [a, b]),
        max = Math.max.apply(Math, [a, b]);
    return this > min && this < max;
};

Number.prototype.inbound = function(a, b) {
    let min = Math.min.apply(Math, [a, b]),
        max = Math.max.apply(Math, [a, b]);
    return this >= min && this <= max;
};

$.fn.escape = function (callback) {
    return this.each(function () {
        $(document).on("keydown", this, function (e) {
            let keycode = ((typeof e.keyCode != 'undefined' && e.keyCode) ? e.keyCode : e.which);
            if (keycode === 27) {
                callback.call(this, e);
            }
        });
    });
};
/* ==================================================== create map =================================================== */
setup_MapCreate = function(target, theMap) {
    var map = null;

    switch (theMap.map.type) {
        case 'bitmap': {
            map = L.map(target, {
                crs: L.CRS.Simple,
                minZoom: theMap['display']['zoom_min'],
                maxZoom: theMap['display']['zoom_max'],
                preferCanvas: true,
                renderer: L.canvas(),
                zoomControl: false,
            });
            map.addControl(new L.Control.Zoomslider({position: 'bottomright'}));

            break;
        }
        case 'vector': {
            map = L.map(target, {
                crs: L.CRS.Simple,
                minZoom: theMap['display']['zoom_min'],
                maxZoom: theMap['display']['zoom_max'],
                preferCanvas: true,
                renderer: L.canvas(),
                zoomControl: false,
            });
            map.addControl(new L.Control.Zoomslider({position: 'bottomright'}));

            break;
        }
        case 'tileset': {
            break;
        }
    }

    return map;
}

setup_MapSetMaxBounds = function(map, theMap) {
    var base_map_bounds  = [ [0, 0], [theMap['map']['height'], theMap['map']['width'] ] ];
    if (theMap['maxbounds']) {
        var mb = theMap['maxbounds'];
        map.setMaxBounds([ [ mb['topleft_h'] * theMap['map']['height'], mb['topleft_w'] * theMap['map']['width'] ]  , [ mb['bottomright_h'] * theMap['map']['height'], mb['bottomright_w'] * theMap['map']['width'] ] ]);
    }
    return base_map_bounds;
}

setup_MapCreateOverlay = function(map, theMap, bounds) {
    var image = null;

    switch (theMap.map.type) {
        case 'bitmap': {
            image = L.imageOverlay( theMap['map']['imagefile'], base_map_bounds).addTo(map);

            break;
        }
        case 'vector': {
            image = L.imageOverlay( theMap['map']['imagefile'], base_map_bounds).addTo(map);


            break;
        }
        case 'tileset': {
            L.tileLayer('eso/{z}/{x}/{y}.jpg', {
                minZoom: theMap['display']['zoom_min'],
                maxZoom: theMap['display']['zoom_max'],
                attribution: 'ESO/INAF-VST/OmegaCAM',
                tms: true
            }).addTo(map);

            break;
        }
    }

    return image;

};


/* ==================================================== show content ================================================= */

/**
 * Показывает контентное окно colorbox'ом.
 *
 * @param id_region
 * @param title
 *
 * @global map_alias, colorbox_width, colorbox_height
 */
showContentColorbox = function(id_region , title) {
    var is_iframe = ((window != window.top || document != top.document || self.location != top.location)) ? '&resultType=iframe' : '';
    var url = URL_GETREGIONCONTENT + map_alias + '&id=' + id_region + is_iframe;

    $.get( url, function() {
    }).done(function(data) {
        var colorbox_width  = 800;
        var colorbox_height = 600;
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

do_LoadContent = function(id_region) {
    if (!(id_region in polymap)) {
        console.log("[" + id_region + "] not found at polymap.");
        return false;
    }

    if (IS_DEBUG) console.log("Called do_LoadContent for " + id_region);

    if (current_infobox_region_id != id_region) {
        let url = URL_GETREGIONCONTENT + map_alias + '&id=' + id_region;

        $.get(url, function(){}).done(function(data){
            if (IS_DEBUG) console.log('data loaded, length ' + data.length);

            current_infobox_region_id = id_region;

            $("#section-infobox-content").html('').html(data);
            document.getElementById('section-infobox-content').scrollTop = 0; // scroll box to top
        });
    }
}

manageInfoBox = function(event, id_region) {
    if (!__InfoBox) {
        __InfoBox = new L.Control.InfoBox();
        map.addControl( __InfoBox );
    }

    let $infobox = $("#section-infobox");
    let $infobox_toggle_buttpon = $('#actor-section-infobox-toggle');
    var current_infobox_visible_state = $infobox_toggle_buttpon.data('content-visibility');

    // if (IS_DEBUG) console.log("Event: " + event + " for region " + id_region);
    // if (IS_DEBUG) console.log('Current infobox visibility state: ' + current_infobox_visible_state);

    switch (event) {
        case 'show': {
            do_LoadContent(id_region);

            window.location.hash = "#view=[" + id_region + "]";

            current_infobox_visible_state = true;

            $infobox.show();
            break;
        }
        case 'hide': {
            current_infobox_visible_state = false;

            history.pushState('', document.title, window.location.pathname);

            $infobox.hide();
            break;
        }
        case 'toggle': {
            if (current_infobox_visible_state) {
                history.pushState('', document.title, window.location.pathname);
                current_infobox_visible_state = false;
            } else {
                current_infobox_visible_state = true;
                window.location.hash = "#view=[" + id_region + "]";
            }
            $infobox.toggle();
            break;
        }
    }
    $infobox_toggle_buttpon.data('content-visibility', current_infobox_visible_state);
}


/* ==================================================== end: show content ============================================ */

do_HighlightRegion = function(id_region) {

}




/* ==================================================== begin: focus ================================================= */
onclick_FocusRegion = function(id_region){
    var id_layer = theMap['regions'][id_region]['layer'];
    var is_visible = LGS[id_layer].visible;
    var bounds;

    // if (IS_DEBUG) console.log("onclick_FocusRegion -> layer " + id_layer + " is_visible " + is_visible);
    // if (IS_DEBUG) console.log( LGS[id_layer].actor );

    // сохраняем оригинальный стиль региона
    var old_style = polymap[id_region].options['fillColor'];

    if (!is_visible) {
        map.setZoom( theMap['layers'][id_layer]['zoom'], {
            animate: false
        } );
        bounds = polymap[id_region].getBounds();

        polymap[ id_region ].setStyle({fillColor: focus_highlight_color}); // подсвечиваем (перенести в функцию/метод объекта)

        map.panTo( bounds.getCenter(), { animate: true, duration: 1, noMoveStart: true});
    } else {
        bounds = polymap[id_region].getBounds();

        polymap[ id_region ].setStyle({fillColor: focus_highlight_color}); // подсвечиваем (перенести в функцию/метод объекта)

        map.panTo( bounds.getCenter(), { animate: true, duration: 1, noMoveStart: true});
    }

    // восстанавливаем по таймауту
    setTimeout(function(){
        polymap[id_region].setStyle({fillColor: old_style});
    }, focus_timeout);


};

wlh_FocusRegion = function(id_region){
    /* позиционируем */
    var id_layer = theMap['regions'][id_region]['layer'];
    var is_visible = LGS[id_layer].visible;
    var bounds;

    if (IS_DEBUG) console.log("Текущий зум: ", map.getZoom());
    if (IS_DEBUG) console.log("Запрашиваемый регион: " , id_region);
    if (IS_DEBUG) console.log("принадлежит группе слоёв " , id_layer);
    if (IS_DEBUG) console.log("Видимость группы слоёв с регионом: " , is_visible);
    if (IS_DEBUG) console.log("Описание группы слоёв: ", LGS[id_layer]);


    var zmin = LGS[id_layer].zoom_min;
    var zmax = LGS[id_layer].zoom_max;

    if (IS_DEBUG) console.log("Зум слоя (из инфо карты)", theMap['layers'][id_layer]['zoom']);
    if (IS_DEBUG) console.log("Зум слоя (из layergroup)", LGS[id_layer]['zoom']);

    var currentZoom = map.getZoom();

    // добавляем все слои
    Object.keys( LGS ).forEach(function(lg){
        map.addLayer( LGS[lg].actor );
        LGS[lg].visible = true;
    });

    map.fitBounds(base_map_bounds);

    // зум
    map.setZoom( LGS[id_layer]['zoom'], {
        animate: false
    });

    // пан
    bounds = polymap[id_region].getBounds();
    map.panTo( bounds.getCenter(), { animate: false, duration: 1, noMoveStart: true});

    // удаляем все невидные слои
    Object.keys( LGS ).forEach(function(lg){
        if (!(theMap['layers'][id_layer]['zoom'].inbound(zmin, zmax))) {
            if (IS_DEBUG) console.log('Надо скрыть слой ' + lg);

            map.removeLayer( LGS[id_layer].actor );
            LGS[id_layer].visible = false;
        }
    });

}



/* ==================================================== end: focus =================================================== */

/* ==================================================== begin: create controls ======================================= */
/**
 * Создает в объекте L Control-элемент: имя региона (для карт типа folio)
 */
createControl_RegionTitle = function(position){
    // return L.control.extend делать нельзя (ошибка TypeError: t.addTo is not a function )
    L.Control.Title = L.Control.extend({
        options: {
            position: position || 'topleft'
        },
        onAdd: function(map) {
            var div = L.DomUtil.get('section-region-title');
            L.DomUtil.removeClass(div, 'invisible');
            L.DomEvent.disableScrollPropagation(div);
            L.DomEvent.disableClickPropagation(div);
            return div;
        },
        onRemove: function(map){}
    });
};

/**
 * Создает в объекте L Control-элемент: список регионов
 */
createControl_RegionsBox = function() {
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
};

/**
 * Создает в объекте L Control элемент: информация о регионе
 */
createControl_InfoBox = function(){
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
};

/**
 * Создает в объекте L Control элемент: кнопка "назад"
 */
createControl_Backward = function(position){
    L.Control.Backward = L.Control.extend({
        options: {
            position: position || 'bottomleft'
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
};

/* ==================================================== end: create controls ========================================= */

/* ==================================================== toggles ====================================================== */

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
toggle_BackwardBox = function(el){
    var state = $(el).data('content-is-visible');
    var text = (state == false) ? '&lt;' : '&gt;';
    $(this).html(text);

    var data = $(el).data('content');
    $('#' + data).toggle();
    $(this).data('content-is-visible', !state);
}
/* ==================================================== end: toggles ================================================= */

/* ==================================================== create map regions =========================================== */
/**
 * Возвращает объект, содержащий все регионы.
 *
 * @param theMap
 * @param layer
 * @returns {Object}
 */
buildPolymap = function(theMap) {
    var polymap = Object.create(null);

    Object.keys( theMap.regions ).forEach(function( key ){
        var region = theMap.regions[ key ];
        var type = region['type'];
        var coords = region['coords'];

        // DEFAULTS for ALL polygons
        var options = {
            color: region['borderColor']      ||  theMap.region_defaults_empty.borderColor,
            weight: region['borderWidth']      ||  theMap.region_defaults_empty.borderWidth,
            opacity: region['borderOpacity']    ||  theMap.region_defaults_empty.borderOpacity,
            fillColor: region['fillColor']  ||  theMap.region_defaults_empty.fillColor,
            fillOpacity: region['fillOpacity'] || theMap.region_defaults_empty.fillOpacity,
            radius: region['radius'] || 10
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

    return polymap;
}

/* ==================================================== end: create map regions ====================================== */

/* ==================================================== Window Location Hash based =================================== */

/**
 * Анализируетм Window.Location.Hash и определяем опции фокусировки/показа региона.
 * Возвращаем опции действия.
 *
 * Следует учитывать, что на карте может не быть региона, переданного в параметрах. Для обработки этой ситуации
 * передается массив карты и имя текущего слоя.
 *
 * @param polymap
 * @param layer
 * @returns {boolean}
 */
wlhBased_GetAction = function(polymap) {
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

/* ===================================== РЕФАКТОРИНГ ============================ */


do_ManageRegionContent = function(id_region) {

}

/* === DEPRECATED AND UNUSED CODE === */

if (false) {
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
                toggleContentViewBox(href_params[1], '');
            }
        } else {
            window.location.assign(href);
            window.location.reload(true);
        }

        return false;
    });
}

/* global window, exports, define */

!function() {
    'use strict'

    var re = {
        not_string: /[^s]/,
        not_bool: /[^t]/,
        not_type: /[^T]/,
        not_primitive: /[^v]/,
        number: /[diefg]/,
        numeric_arg: /[bcdiefguxX]/,
        json: /[j]/,
        not_json: /[^j]/,
        text: /^[^\x25]+/,
        modulo: /^\x25{2}/,
        placeholder: /^\x25(?:([1-9]\d*)\$|\(([^)]+)\))?(\+)?(0|'[^$])?(-)?(\d+)?(?:\.(\d+))?([b-gijostTuvxX])/,
        key: /^([a-z_][a-z_\d]*)/i,
        key_access: /^\.([a-z_][a-z_\d]*)/i,
        index_access: /^\[(\d+)\]/,
        sign: /^[+-]/
    }

    function sprintf(key) {
        // `arguments` is not an array, but should be fine for this call
        return sprintf_format(sprintf_parse(key), arguments)
    }

    function vsprintf(fmt, argv) {
        return sprintf.apply(null, [fmt].concat(argv || []))
    }

    function sprintf_format(parse_tree, argv) {
        var cursor = 1, tree_length = parse_tree.length, arg, output = '', i, k, ph, pad, pad_character, pad_length, is_positive, sign
        for (i = 0; i < tree_length; i++) {
            if (typeof parse_tree[i] === 'string') {
                output += parse_tree[i]
            }
            else if (typeof parse_tree[i] === 'object') {
                ph = parse_tree[i] // convenience purposes only
                if (ph.keys) { // keyword argument
                    arg = argv[cursor]
                    for (k = 0; k < ph.keys.length; k++) {
                        if (arg == undefined) {
                            throw new Error(sprintf('[sprintf] Cannot access property "%s" of undefined value "%s"', ph.keys[k], ph.keys[k-1]))
                        }
                        arg = arg[ph.keys[k]]
                    }
                }
                else if (ph.param_no) { // positional argument (explicit)
                    arg = argv[ph.param_no]
                }
                else { // positional argument (implicit)
                    arg = argv[cursor++]
                }

                if (re.not_type.test(ph.type) && re.not_primitive.test(ph.type) && arg instanceof Function) {
                    arg = arg()
                }

                if (re.numeric_arg.test(ph.type) && (typeof arg !== 'number' && isNaN(arg))) {
                    throw new TypeError(sprintf('[sprintf] expecting number but found %T', arg))
                }

                if (re.number.test(ph.type)) {
                    is_positive = arg >= 0
                }

                switch (ph.type) {
                    case 'b':
                        arg = parseInt(arg, 10).toString(2)
                        break
                    case 'c':
                        arg = String.fromCharCode(parseInt(arg, 10))
                        break
                    case 'd':
                    case 'i':
                        arg = parseInt(arg, 10)
                        break
                    case 'j':
                        arg = JSON.stringify(arg, null, ph.width ? parseInt(ph.width) : 0)
                        break
                    case 'e':
                        arg = ph.precision ? parseFloat(arg).toExponential(ph.precision) : parseFloat(arg).toExponential()
                        break
                    case 'f':
                        arg = ph.precision ? parseFloat(arg).toFixed(ph.precision) : parseFloat(arg)
                        break
                    case 'g':
                        arg = ph.precision ? String(Number(arg.toPrecision(ph.precision))) : parseFloat(arg)
                        break
                    case 'o':
                        arg = (parseInt(arg, 10) >>> 0).toString(8)
                        break
                    case 's':
                        arg = String(arg)
                        arg = (ph.precision ? arg.substring(0, ph.precision) : arg)
                        break
                    case 't':
                        arg = String(!!arg)
                        arg = (ph.precision ? arg.substring(0, ph.precision) : arg)
                        break
                    case 'T':
                        arg = Object.prototype.toString.call(arg).slice(8, -1).toLowerCase()
                        arg = (ph.precision ? arg.substring(0, ph.precision) : arg)
                        break
                    case 'u':
                        arg = parseInt(arg, 10) >>> 0
                        break
                    case 'v':
                        arg = arg.valueOf()
                        arg = (ph.precision ? arg.substring(0, ph.precision) : arg)
                        break
                    case 'x':
                        arg = (parseInt(arg, 10) >>> 0).toString(16)
                        break
                    case 'X':
                        arg = (parseInt(arg, 10) >>> 0).toString(16).toUpperCase()
                        break
                }
                if (re.json.test(ph.type)) {
                    output += arg
                }
                else {
                    if (re.number.test(ph.type) && (!is_positive || ph.sign)) {
                        sign = is_positive ? '+' : '-'
                        arg = arg.toString().replace(re.sign, '')
                    }
                    else {
                        sign = ''
                    }
                    pad_character = ph.pad_char ? ph.pad_char === '0' ? '0' : ph.pad_char.charAt(1) : ' '
                    pad_length = ph.width - (sign + arg).length
                    pad = ph.width ? (pad_length > 0 ? pad_character.repeat(pad_length) : '') : ''
                    output += ph.align ? sign + arg + pad : (pad_character === '0' ? sign + pad + arg : pad + sign + arg)
                }
            }
        }
        return output
    }

    var sprintf_cache = Object.create(null)

    function sprintf_parse(fmt) {
        if (sprintf_cache[fmt]) {
            return sprintf_cache[fmt]
        }

        var _fmt = fmt, match, parse_tree = [], arg_names = 0
        while (_fmt) {
            if ((match = re.text.exec(_fmt)) !== null) {
                parse_tree.push(match[0])
            }
            else if ((match = re.modulo.exec(_fmt)) !== null) {
                parse_tree.push('%')
            }
            else if ((match = re.placeholder.exec(_fmt)) !== null) {
                if (match[2]) {
                    arg_names |= 1
                    var field_list = [], replacement_field = match[2], field_match = []
                    if ((field_match = re.key.exec(replacement_field)) !== null) {
                        field_list.push(field_match[1])
                        while ((replacement_field = replacement_field.substring(field_match[0].length)) !== '') {
                            if ((field_match = re.key_access.exec(replacement_field)) !== null) {
                                field_list.push(field_match[1])
                            }
                            else if ((field_match = re.index_access.exec(replacement_field)) !== null) {
                                field_list.push(field_match[1])
                            }
                            else {
                                throw new SyntaxError('[sprintf] failed to parse named argument key')
                            }
                        }
                    }
                    else {
                        throw new SyntaxError('[sprintf] failed to parse named argument key')
                    }
                    match[2] = field_list
                }
                else {
                    arg_names |= 2
                }
                if (arg_names === 3) {
                    throw new Error('[sprintf] mixing positional and named placeholders is not (yet) supported')
                }

                parse_tree.push(
                    {
                        placeholder: match[0],
                        param_no:    match[1],
                        keys:        match[2],
                        sign:        match[3],
                        pad_char:    match[4],
                        align:       match[5],
                        width:       match[6],
                        precision:   match[7],
                        type:        match[8]
                    }
                )
            }
            else {
                throw new SyntaxError('[sprintf] unexpected placeholder')
            }
            _fmt = _fmt.substring(match[0].length)
        }
        return sprintf_cache[fmt] = parse_tree
    }

    /**
     * export to either browser or node.js
     */
    /* eslint-disable quote-props */
    if (typeof exports !== 'undefined') {
        exports['sprintf'] = sprintf
        exports['vsprintf'] = vsprintf
    }
    if (typeof window !== 'undefined') {
        window['sprintf'] = sprintf
        window['vsprintf'] = vsprintf

        if (typeof define === 'function' && define['amd']) {
            define(function() {
                return {
                    'sprintf': sprintf,
                    'vsprintf': vsprintf
                }
            })
        }
    }
    /* eslint-enable quote-props */
}();