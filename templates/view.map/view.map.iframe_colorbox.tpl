<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>{$html_title}</title>

    {include file="_common/favicon_defs.tpl"}

    <link rel="stylesheet" href="/frontend/leaflet/leaflet.css">
    <link rel="stylesheet" href="/frontend/view.map.fullscreen.css" />
    {if !empty($custom_css)}
        <link rel="stylesheet" href="{$custom_css}" />
    {/if}

    <script type="text/javascript" src="/frontend/jquery/jquery-3.2.1_min.js"></script>
    <script type="text/javascript" src="/frontend/leaflet/leaflet.js"></script>

    <script type="text/javascript" src="/frontend/leaflet/L.Control.Zoomslider.js"></script>
    <link rel="stylesheet" href="/frontend/leaflet/L.Control.Zoomslider.css" />

    <script type="text/javascript" src="/frontend/colorbox/jquery.colorbox-min.js"></script>

    <script src="/map:js/{$map_alias}.js" id="the-map-data"></script>
    <script type="text/javascript" src="/frontend/livemap.view.js" id="livemap-view-map-methods"></script>

    <script type="text/javascript" id="init">
        let map_alias = '{$map_alias}';
        let regions_with_content_ids = [
            {$regions_with_content_ids}
        ];
    </script>
</head>
<body>

<div tabindex="0" class="leaflet-container leaflet-fade-anim leaflet-grab leaflet-touch-drag" id="map"></div>

<div style="display:none">
    <div id="colorboxed-view" style="padding:10px; background:#fff;">
        <div id="colorboxed-view-content"></div>
    </div>
</div>

<section id="section-backward" class="invisible section-backward-viewbox">
    <button id="actor-backward-toggle" class="action-toggle-div-visibility" data-content="section-backward-content" data-content-is-visible="false">&gt;</button>
    <span id="section-backward-content" class="invisible section-backward-content">
        <form style="display: inline-block" class="invisible" action="{$html_callback}" method="get"><button><<< К списку карт</button></form>
    </span>
</section>


<script type="text/javascript" src="/frontend/view.map.iframe_colorbox.js"></script>

</body>
</html>