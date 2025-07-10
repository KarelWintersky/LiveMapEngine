{if $is_can_edit}
    <button type="button"
            data-action="redirect"
            data-url="{$edit_button_url}?map={$map_alias}">Редактировать</button>
    <hr>
{/if}
<div style="padding: 8px">
    {if $is_present}
        {if $title}
            <h3>{$title}</h3>
        {/if}
        {$content}
    {else}
        Нет информации!
    {/if}
</div>
