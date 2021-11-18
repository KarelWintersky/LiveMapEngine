<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>{$html_title}</title>
    <link rel="stylesheet" href="/frontend/leaflet/leaflet.css">
    <link rel="stylesheet" href="/frontend/view.map.fullscreen.css" />
    {?*custom_css*}<link rel="stylesheet" href="{*custom_css*}" />{?}

    <script type="text/javascript" src="/frontend/jquery/jquery-3.2.1_min.js"></script>
    <script type="text/javascript" src="/frontend/leaflet/leaflet.js"></script>

    <script type="text/javascript" src="/frontend/leaflet/L.Control.Zoomslider.js"></script>
    <link rel="stylesheet" href="/frontend/leaflet/L.Control.Zoomslider.css" />

    <script type="text/javascript" src="/frontend/livemap.view.js" id="livemap-view-map-methods"></script>
    <script type="text/javascript" src="/js/map/{*map_alias*}.js" id="the-map-outer"></script>
</head>
<body>
<script type="text/javascript" id="init">
    var template_orientation = -1; // инфо слева: -1, инфо справа: +1
    var map_centring_panning_step = +"{*panning_step*}";  // на сколько пикселей при позиционировании региона "по центру" он будет сдвинут
    var map_alias = '{*map_alias*}';

    var regions_with_content_ids = [
        {*regions_with_content_ids*}
    ];
</script>

<div tabindex="0" class="leaflet-container leaflet-fade-anim leaflet-grab leaflet-touch-drag" id="map"></div>

<section id="section-infobox" class="section-infobox-wrapper invisible" data-leaflet-control-position="{*section.infobox_control_position*}">
    <div style="text-align: right">
        <button id="actor-section-infobox-toggle" class="section-infobox-button-toggle-visibility" data-content="section-info-content" data-content-visibility="false">Скрыть</button>
    </div>
    <div id="section-infobox-content" class="section-infobox-content"></div>
</section>

<section id="section-regions" class="section-regions-viewbox invisible" data-leaflet-control-position="{*section.regionbox_control_position*}">
    <div style="text-align: {*section.regionbox_textalign*}">
        <button id="actor-regions-toggle" class="action-toggle-div-visibility" data-content="section-regions-content" data-content-is-visible="false">Показать</button>
        <h3>Интересные места на карте {?*map_regions_count*}<span style="font-weight: normal">(<em >Всего: {*map_regions_count*}</em>)</span>{*map_regions_count*?}</h3>
        <select id="sort-select" class="invisible">
            <option value="total" data-ul="data-ordered-alphabet">... все</option>
            <option value="latest" data-ul="data-ordered-latest">... новые</option>
        </select>
        &nbsp;&nbsp;
    </div>

    {?!*target*}
    <div id="section-regions-content" class="invisible section-regions-content">
        <ul class="map-regions" id="data-ordered-alphabet">
            {%*map_regions_order_by_title*}<li><a class="action-focus-at-region" href="#focus=[{*map_regions_order_by_title:id_region*}]" data-region-id="{*map_regions_order_by_title:id_region*}">{*map_regions_order_by_title:title*}</a></li>
            {*map_regions_order_by_title*%}
        </ul>

        <ul class="map-regions invisible" id="data-ordered-latest">
            {%*map_regions_order_by_date*}<li><a class="action-focus-at-region" href="#focus=[{*map_regions_order_by_date:id_region*}]" data-region-id="{*map_regions_order_by_date:id_region*}">{*map_regions_order_by_date:title*}</a>
            <br/><small>({*map_regions_order_by_date:edit_date*})</small></li>
            {*map_regions_order_by_date*%}
        </ul>
    </div>
    {*target*?!}
</section>

<section id="section-backward" class="invisible section-backward-viewbox">
    <button id="actor-backward-toggle" class="action-toggle-div-visibility" data-content="section-backward-content" data-content-is-visible="false">&gt;</button>
    <span id="section-backward-content" class="invisible section-backward-content">
        <form style="display: inline-block" class="invisible" action="{*html_callback*}" method="get"><button><<< К списку карт</button></form>
    </span>
</section>

<!-- <script type="text/javascript" src="/frontend/view.map.fullscreen-old.js"></script> -->
<script type="text/javascript" src="/frontend/view.map.fullscreen.js"></script>

</body>
</html>