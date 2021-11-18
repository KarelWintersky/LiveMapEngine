var theMap = {
    {if $JSBuilderError}"JSBuilderError": "{$JSBuilderError}",{/if}

    "map": {
        "title"             :   "{$map.title}",
        "type"              :   "{$map.type}",
        "imagefile"         :   "/storage/{$map.alias}/{$map.imagefile}",
        "width"             :   {$map.width},
        "height"            :   {$map.height},
        "orig_x"            :   {$map.ox},
        "orig_y"            :   {$map.oy},
    },

    {if $source_image}
    "image": {
        "file"              :   "/storage/{$map.alias}/{$source_image.file}",
        "width"             :   {$source_image.width},
        "height"            :   {$source_image.height},
        "orig_x"            :   {$source_image.ox},
        "orig_y"            :   {$source_image.oy},
    },
    {/if}

    "display": {
        "zoom"              :   {$display.zoom},
        "zoom_min"          :   {$display.zoom_min},
        "zoom_max"          :   {$display.zoom_max},
        "background_color"  :   "{$display.background_color}",

        {if $display.custom_css}"custom_css" : "{$display.custom_css}",{/if}

        {if $maxbounds}

        "maxbounds": {
            {foreach $maxbounds as $key => $value}

            "{$key}": "{$value}",

            {/foreach}
        },
        {/if}

    {if $focus_animate_duration}
        "focus_animate_duration": {$focus_animate_duration},
    {/if}

    {if $focus_highlight_color}
        "focus_highlight_color": "{$focus_highlight_color}",
    {/if}

    {if $focus_timeout}
        "focus_timeout": {$focus_timeout},
    {/if}

    },

    "region_defaults_empty" : {
        "stroke" : {$region_defaults_empty.stroke},
        "borderColor" : "{$region_defaults_empty.borderColor}",
        "borderWidth" : {$region_defaults_empty.borderWidth},
        "borderOpacity" : {$region_defaults_empty.borderOpacity},
        "fill" : {$region_defaults_empty.fill},
        "fillColor" : "{$region_defaults_empty.fillColor}",
        "fillOpacity" : {$region_defaults_empty.fillOpacity},
    },

    "region_defaults_present": {
        "stroke" : {$region_defaults_present.stroke},
        "borderColor" : "{$region_defaults_present.borderColor}",
        "borderWidth" : {$region_defaults_present.borderWidth},
        "borderOpacity" : {$region_defaults_present.borderOpacity},
        "fill" : {$region_defaults_present.fill},
        "fillColor" : "{$region_defaults_present.fillColor}",
        "fillOpacity" : {$region_defaults_present.fillOpacity},
    },

    "layers": {

    {foreach $layers as $layer}

        "{$layer.id}": {
            "id" : "{$layer.id}",
            "hint" : "{$layer.hint}",
            "zoom" : {$layer.zoom},
            "zoom_min" : {$layer.zoom_min},
            "zoom_max" : {$layer.zoom_max},
        },

    {/foreach}
    },

    "regions": {
    {foreach $regions as $region}

        "{$region.id}": {
            "id"        : "{$region.id}",
            "type"      : "{$region.type}",
            "coords"    : {$region.js},
            "layer"     : "{$region.layer}",

            {if $region.fillColor}"fillColor" : "{$region.fillColor}", {/if}

            {if $region.fillOpacity}"fillOpacity": {$region.fillOpacity}, {/if}

            {if $region.fillRule}"fillRule": "{$region.fillRule}",{/if}

            {if $region.borderColor}"borderColor": "{$region.borderColor}",{/if}

            {if $region.borderWidth}"borderWidth": "{$region.borderWidth}",{/if}

            {if $region.borderOpacity}"borderOpacity": "{$region.borderOpacity}",{/if}

            {if $region.title}"title": "{$region.title}",{/if}

            {if $region.edit_date}"edit_date": "{$region.edit_date}",{/if}

            {if $region.desc}"desc": "{$region.desc}",{/if}

            {if $region.radius}"radius": "{$region.radius}",{/if}

            {if $region.is_excludelists}"is_excludelists": "{$region.is_excludelists}",{/if}

        },

    {/foreach}
    }
};

