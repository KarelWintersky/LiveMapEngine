<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>{$html_title}</title>

    {include file="_common/favicon_defs.tpl"}
    {include file="_common/opengraph.tpl"}

    <link href="https://fonts.googleapis.com/css?family=PT+Serif" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=PT+Sans" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css" rel="stylesheet">

    <link rel="stylesheet" href="/frontend/leaflet/leaflet.css">
    <link rel="stylesheet" href="{$main_css_file}">

    {if !empty($custom_css)}
    <link rel="stylesheet" href="{$custom_css}">
    {/if}

    <script src="/frontend/jquery/jquery-3.2.1_min.js"></script>
    <script src="/frontend/leaflet/leaflet.js"></script>
    <script src="/frontend/scripts.js"></script>
    <script src="/frontend/MapManager.js"></script>
    <script src="/frontend/MapControls.js"></script>

    <script src="/frontend/leaflet/L.Control.Zoomslider.js"></script>
    <link rel="stylesheet" href="/frontend/leaflet/L.Control.Zoomslider.css">

    <script src="/frontend/leaflet/L.Icon.FontAwesome.js"></script>
    <link href="/frontend/leaflet/L.Icon.FontAwesome.css" rel="stylesheet">

    <script src="/frontend/leaflet/SmoothWheelZoom.js"></script>

    {if $sections_present.colorbox}
        <script type="text/javascript" src="/frontend/colorbox/jquery.colorbox-min.js"></script>
        <link rel="stylesheet" href="/frontend/colorbox/colorbox.css">
    {/if}

    <script data-comment="init">
        window.theMap = { };
        window.REGION_URLS = {
            "view": "{Arris\AppRouter::getRouter('view.region.info')}",
            "edit": "{Arris\AppRouter::getRouter('edit.region.info')}"
        };

        var map_alias = '{$map_alias}';

        var template_orientation = -1; // инфо слева: -1, инфо справа: +1
        var map_centring_panning_step = Number("{$panning_step|default:0}");  // на сколько пикселей при позиционировании региона "по центру" он будет сдвинут
    </script>

    <script src="/map:js/{$map_alias}.js" data-comment="the-map-data"></script>

    <script data-comment="regions">
        let regions_with_content_ids = [
            {$regions_with_content_ids}
        ];
        window._mapManager = new MapManager(window.theMap);
    </script>
</head>
<body>
<div tabindex="0" class="leaflet-container leaflet-fade-anim leaflet-grab leaflet-touch-drag" id="map"></div>

{if $sections_present.colorbox}
    <div style="display:none">
        <div id="colorboxed-view" style="padding:10px; background:#fff;">
            <div id="colorboxed-view-content"></div>
        </div>
    </div>
{/if}

{if $sections_present.infobox}
    <section id="section-infobox" class="section-infobox-wrapper invisible" data-leaflet-control-position="{$section.infobox_control_position}">
        <div style="text-align: right">
            <button id="actor-section-infobox-toggle" class="section-infobox-button-toggle-visibility" data-content="section-info-content" data-content-visibility="false">Скрыть</button>
        </div>
        <div id="section-infobox-content" class="section-infobox-content"></div>
    </section>
{/if}

{if $sections_present.regions}
    <section id="section-regions" class="section-regions-viewbox invisible" data-leaflet-control-position="{$section.regionbox_control_position}">
        <div style="text-align: {*section.regionbox_textalign*}">
            <button id="actor-regions-toggle" class="action-toggle-div-visibility" data-content="section-regions-content" data-content-is-visible="false">Показать</button>
            <h3>Интересные места на карте {if $map_regions_count}<span style="font-weight: normal">(<em >Всего: {$map_regions_count}</em>)</span>{/if}</h3>
            <select id="sort-select" class="invisible">
                <option value="total" data-ul="data-ordered-alphabet">... все</option>
                <option value="latest" data-ul="data-ordered-latest">... новые</option>
            </select>
            &nbsp;&nbsp;
        </div>

        {if !$target}
            <div id="section-regions-content" class="invisible section-regions-content">
                <ul class="map-regions" id="data-ordered-alphabet">
                    {foreach $map_regions_order_by_title as $region}
                        <li>
                            <a class="action-focus-at-region" href="#focus={$region.id_region}" data-region-id="{$region.id_region}">{$region.title}</a>
                        </li>
                    {/foreach}
                </ul>

                <ul class="map-regions invisible" id="data-ordered-latest">
                    {foreach $map_regions_order_by_date as $region}
                        <li>
                            <a class="action-focus-at-region" href="#focus=[{$region.id_region}]" data-region-id="{$region.id_region}">{$region.title}</a>
                            <br/><small>({$region.edit_date})</small>
                        </li>
                    {/foreach}
                </ul>
            </div>
        {/if}
    </section>
{/if}

{if $sections_present.backward}
    {* Если имеет место быть кастомный контент контрола "backward", это поле НЕ пусто *}
    {if $section_backward_content}
        <section id="section-backward" class="invisible section-backward-viewbox" {* data-leaflet-control-position=... *}>
            <button id="actor-backward-toggle" class="action-toggle-div-visibility" data-content="section-backward-content" data-content-is-visible="false">&nbsp;&nbsp;&gt;&nbsp;&nbsp;</button>
            <span id="section-backward-content" class="invisible section-backward-content">
                &nbsp;
                {foreach $section_backward_content as $section_backward_content_button}
                <button style="display: inline-block" type="button" data-action="redirect" data-url="{$section_backward_content_button.link}">&lt;&lt;&lt; {$section_backward_content_button.text}</button>
                {if !$section_backward_content_button@last}&nbsp;|&nbsp;{/if} {* вставляем | только если это НЕ последний элемент в этом форыче.  *}
            {/foreach}
            </span>
        </section>
    {else}
        {* Значение по-умолчанию контрола "backward" *}
        {* Хотя можно было бы сделать форыч всегда и значение по-дефолту передавать из MapsController, я оставлю этот механизм. Он нагляднее *}
        <section id="section-backward" class="invisible section-backward-viewbox" {* data-leaflet-control-position=... *}>
            <button id="actor-backward-toggle" class="action-toggle-div-visibility" data-content="section-backward-content" data-content-is-visible="false">&nbsp;&nbsp;&gt;&nbsp;&nbsp;</button>
            <span id="section-backward-content" class="invisible section-backward-content">
                <button style="display: inline-block" type="button" data-action="redirect" data-url="{$html_callback}">&lt;&lt;&lt; К списку карт</button>
            </span>
        </section>
    {/if}
{/if}

{if $sections_present.title}
    <section id="section-region-title" class="invisible section-region-title-viewbox" {* data-leaflet-control-position=... *}>
        <span>Selected region: </span><strong id="section-region-title-content" class="section-region-title-content"></strong>
    </section>
{/if}

<script src="{$main_js_file}"></script>

</body>
</html>
{* -eof- *}