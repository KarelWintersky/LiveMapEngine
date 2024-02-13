<iframe src="/map:iframe/pony?view=iframe" width="100%" height="500px" scrolling="no" frameborder="1">
</iframe>
<div class="line"></div>

<h2 id="maps">Карты</h2>

<ul class="components">
    {foreach $maps_list as $map}
        <li>
            <a href="/map/{$map.alias}">{$map.title}</a>
        </li>
    {/foreach}
</ul>

<div class="line"></div>