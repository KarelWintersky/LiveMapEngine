{if $is_present}
    <fieldset class="region-content">
        <legend>{$region_title}</legend>
        {$region_text}
    </fieldset>
{else}
    <br/>По региону <strong>{$region_id}</strong> нет информации.
{/if}
