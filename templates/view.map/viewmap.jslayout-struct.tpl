var theMap = {
    "map" : {
        "title"             :   "{*map.title*}",
        "imagefile"         :   "/storage/{*map.alias*}/{*map.imagefile*}",
        "width"             :   {*map.width*},
        "height"            :   {*map.height*},
        "orig_x"            :   {*map.ox*},
        "orig_y"            :   {*map.oy*},
        "zoom"              :   {*map.default_zoom*}
    },
    "defaults"  : {
        "polygon_color"     :   "{*defaults.color*}",
        "polygon_width"     :    {*defaults.width*},
        "polygon_opacity"   :    {*defaults.opacity*},
        "polygon_fillColor" :   "{*defaults.fillcolor*}",
        "polygon_fillOpacity":   {*defaults.fillopacity*}
    },
    "viewport"  : {
        "width"             : "{*viewport.width*}",
        "height"            : "{*viewport.height*}",
        "background_color"  : "{*viewport.background_color*}"
    },
    "colorbox"  : {
        "width"                 :   "80%",
        "height"                :   "80%"
    },

    {?*maxbounds*}
    "maxbounds" : {
    {%*maxbounds*}
        "{*maxbounds:^KEY*}" : {*maxbounds:*},
    {%}
    },
    {?}

    "regions": {
    {%*regions*}
        "{*regions:id*}" : {
            "id"        : "{*regions:id*}",
            "type"      : "{*regions:type*}",
            "coords"    : {*regions:js*},
{?*regions:fillColor*}            "fillColor" : "{*regions:fillColor*}", {?}
{?*regions:fillOpacity*}            "fillOpacity": {*regions:fillOpacity*}, {?}
{?*regions:fillRule*}            "fillRule": "{*regions:fillRule*}", {?}
{?*regions:title*}            "title": "{*regions:title*}", {?}
{?*regions:desc*}            "desc": "{*regions:desc*}", {?}
{?*regions:radius*}            "radius": {*regions:radius*},{?}
        },
    {%}
    },

};