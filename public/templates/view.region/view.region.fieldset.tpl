{if $is_present}
    {if $can_edit}
        <button type="button" data-region-id="{$region_id}" id="actor-edit">Редактировать</button>
    {/if}

{/if}

{?*is_present*}
{?*can_edit*}{?}
<fieldset class="region-content">
    <legend> {*region_title*} </legend>

    {*region_text*}
</fieldset>
{?}

{?!*is_present*}
{?*can_edit*}<button id="actor-edit" data-region-id="{*region_id*}">Добавить</button>{?}
<br/>По региону <strong>{*region_id*}</strong> нет информации.
{?}