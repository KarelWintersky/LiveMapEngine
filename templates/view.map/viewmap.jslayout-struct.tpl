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
        {?*focus_animate_duration*}"focus_animate_duration": {*focus_animate_duration*}, {?}
        {?*focus_highlight_color*}"focus_highlight_color": "{*focus_highlight_color*}",{?}
        {?*focus_timeout*}"focus_timeout": {*focus_timeout*}, {?}
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
    "layers": {
    {%*layers*}
        "{*layers:id*}": {
            "id" : "{*layers:id*}",
            "zoom_min" : {*layers:zoom_min*},
            "zoom_max" : {*layers:zoom_max*},
            "regions": {
            {%*layers:regions*}
                "{*layers:regions:id*}": {
                    "id"        : "{*layers:regions:id*}",
                    "type"      : "{*layers:regions:type*}",
                    "coords"    : {*layers:regions:js*},
                    {?*layers:regions:fillColor*}"fillColor" : "{*layers:regions:fillColor*}", {?}
                    {?*layers:regions:fillOpacity*}"fillOpacity": {*layers:regions:fillOpacity*}, {?}
                    {?*layers:regions:fillRule*}"fillRule": "{*layers:regions:fillRule*}", {?}

                    {?*layers:regions:borderColor*}"borderColor": "{*layers:regions:borderColor*}", {?}
                    {?*layers:regions:borderWidth*}"borderWidth": "{*layers:regions:borderWidth*}", {?}
                    {?*layers:regions:borderOpacity*}"borderOpacity": "{*layers:regions:borderOpacity*}", {?}

                    {?*layers:regions:title*}"title": "{*layers:regions:title*}", {?}
                    {?*layers:regions:desc*}"desc": "{*layers:regions:desc*}", {?}
                    {?*layers:regions:radius*}"radius": {*layers:regions:radius*},{?}
                },
            {%}
            }
        },
    {%}
    },
};


