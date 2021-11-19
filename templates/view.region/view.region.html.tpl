{if $is_present}
    {if $is_can_edit}
        <button id="actor-edit" data-region-id="{$region_id}">Редактировать</button>
    {/if}
    <div class="region-content">
        <h2>{$region_title}</h2>
        {$region_text}
    </div>
{else}
    {if $is_can_edit}
        <button id="actor-edit" data-region-id="{$region_id}">Добавить</button>
    {/if}
    <br/>По региону <strong>{$region_id}</strong> нет информации.
{/if}
