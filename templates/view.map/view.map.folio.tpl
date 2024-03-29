<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>{$html_title}</title>

    {include file="_common/favicon_defs.tpl"}

    <link rel="stylesheet" href="/frontend/leaflet/leaflet.css" />
    <link rel="stylesheet" href="/frontend/view.map.folio.css">

    <script type="text/javascript" src="/frontend/jquery/jquery-3.2.1_min.js"></script>
    <script type="text/javascript" src="/frontend/leaflet/leaflet.js"></script>

    <script type="text/javascript" src="/frontend/livemap.view.js" id="livemap-view-map-methods"></script>

    <script type="text/javascript" src="/js/map/{$map_alias}.js" id="the-map-outer"></script>

    <script type="text/javascript" src="/frontend/leaflet/L.Control.Zoomslider.js"></script>
    <link rel="stylesheet" href="/frontend/leaflet/L.Control.Zoomslider.css"/>
</head>

<body>
<script type="text/javascript" id="init">
    var map_alias = '{$map_alias}';
</script>

<div tabindex="0" class="leaflet-container leaflet-fade-anim leaflet-grab leaflet-touch-drag" id="map" style="{*viewport_cursor*}"></div>

<section id="section-backward" class="invisible section-backward-viewbox">
    <button id="actor-backward-toggle" class="action-toggle-div-visibility" data-content="section-backward-content" data-content-is-visible="false">&gt;</button>
    <span id="section-backward-content" class="invisible section-backward-content">
        <form style="display: inline-block" class="invisible" action="{$html_callback}" method="get"><button><<< К списку карт</button></form>
    </span>
</section>

<section id="section-region-title" class="invisible section-region-title-viewbox">
    <span>Selected region:</span> <strong id="section-region-title-content" class="section-region-title-content"></strong>
</section>

<script type="text/javascript" src="/frontend/view.map.folio.js"></script>

</body>
</html>
