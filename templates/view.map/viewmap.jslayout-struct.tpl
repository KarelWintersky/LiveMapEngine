var theMap = {
    {?*JSBuilderError*}"error": "{*JSBuilderError*}",{?}
    "map": {
        "title"             :   "{*map.title*}",
        "imagefile"         :   "/storage/{*map.alias*}/{*map.imagefile*}",
        "width"             :   {*map.width*},
        "height"            :   {*map.height*},
        "orig_x"            :   {*map.ox*},
        "orig_y"            :   {*map.oy*},
    },
    "display": {
        "zoom"              :   {*display.zoom*},
        "zoom_min"          :   {*display.zoom_min*},
        "zoom_max"          :   {*display.zoom_max*},
        "background_color"  :   "{*display.background_color*}",
        {?*display.custom_css*}"custom_css" : "{*display.custom_css*}",{?}
        {?*maxbounds*}
        "maxbounds" : {
        {%*maxbounds*}
            "{*maxbounds:^KEY*}" : {*maxbounds:*},
        {%}
        },
        {?}
    },
    "region_defaults_empty" : {
        "stroke" : {*region_defaults_empty.stroke*},
        "borderColor" : "{*region_defaults_empty.borderColor*}",
        "borderWidth" : {*region_defaults_empty.borderWidth*},
        "borderOpacity" : {*region_defaults_empty.borderOpacity*},
        "fill" : {*region_defaults_empty.fill*},
        "fillColor" : "{*region_defaults_empty.fillColor*}",
        "fillOpacity" : {*region_defaults_empty.fillOpacity*},
    },
    "region_defaults_present": {
        "stroke" : {*region_defaults_present.stroke*},
        "borderColor" : "{*region_defaults_present.borderColor*}",
        "borderWidth" : {*region_defaults_present.borderWidth*},
        "borderOpacity" : {*region_defaults_present.borderOpacity*},
        "fill" : {*region_defaults_present.fill*},
        "fillColor" : "{*region_defaults_present.fillColor*}",
        "fillOpacity" : {*region_defaults_present.fillOpacity*},
    },
    "regions": {
    {%*regions*}
        "{*regions:id*}" : {
            "id"        : "{*regions:id*}",
            "type"      : "{*regions:type*}",
            "coords"    : {*regions:js*},
            "layer"     : "{*regions:layer*}",

            {?*regions:fillColor*}"fillColor" : "{*regions:fillColor*}", {?}
            {?*regions:fillOpacity*}"fillOpacity": {*regions:fillOpacity*}, {?}
            {?*regions:fillRule*}"fillRule": "{*regions:fillRule*}", {?}

            {?*regions:borderColor*}"borderColor": "{*regions:borderColor*}", {?}
            {?*regions:borderWidth*}"borderWidth": "{*regions:borderWidth*}", {?}
            {?*regions:borderOpacity*}"borderOpacity": "{*regions:borderOpacity*}", {?}

            {?*regions:title*}"title": "{*regions:title*}", {?}
            {?*regions:desc*}"desc": "{*regions:desc*}", {?}
            {?*regions:radius*}"radius": {*regions:radius*},{?}
        },
    {%}
    },
};


