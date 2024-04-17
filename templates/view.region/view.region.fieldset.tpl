{if $is_present}
    {if $can_edit}
        <button type="button" data-region-id="{$region_id}" id="actor-edit">Редактировать</button>
    {/if}
    <fieldset class="region-content">
        <legend>{$region_title}</legend>
        {$region_text}
    </fieldset>
{else}
    {if $can_edit}
        <button type="button" data-region-id="{$region_id}" id="actor-edit">Добавить</button>
    {/if}
    <br>
    По региону <strong>{$region_id}</strong> нет информации.
{/if}
