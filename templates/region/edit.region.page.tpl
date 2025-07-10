<!DOCTYPE html>
<html lang="ru">
<head>
    <title>Карта {$title_map}, редактирование региона {$id_region}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">

    {include file="_common/favicon_defs.tpl"}

    <link rel="stylesheet" type="text/css" href="/frontend/edit.region.css" />

    <script type="text/javascript" src="/frontend/html5shiv.js"></script>
    <script type="text/javascript" src="/frontend/jquery/jquery-3.2.1_min.js"></script>
    <script type="text/javascript" src="/frontend/tinymce/tinymce.min.js"></script>
    <script type="text/javascript" id="define">
        window.editor_config = {
            success_edit_timeout: 1000
        };
        const tiny_config = {
            theme: "modern",
            skin: "lightgray",
            language: 'ru',

            forced_root_block: "",
            force_br_newlines: true,
            force_p_newlines: false,

            height: 300,

            plugins: ["advlist lists autolink link image anchor responsivefilemanager charmap insertdatetime paste searchreplace contextmenu code textcolor template hr pagebreak table print preview wordcount visualblocks visualchars legacyoutput"],
            formats: {
                strikethrough: {
                    inline: 'del'
                },
                underline: {
                    inline: 'span',
                    'classes': 'underline',
                    exact: true
                }
            },

            insertdatetime_formats: [
                "%d.%m.%Y", "%H:%m", "%d/%m/%Y"
            ],
            contextmenu: "link image responsivefilemanager | inserttable cell row column deletetable | charmap",
            toolbar1: "undo redo | bold italic underline subscript superscript strikethrough | fontsizeselect styleselect | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist | ",
            toolbar2: "responsivefilemanager image | template table charmap | link unlink anchor | pastetext removeformat | preview",

            // charmap https://www.tinymce.com/docs/plugins/charmap/
            // https://stackoverflow.com/a/22156412/5127037
            charmap_append: [
                ["0x27f7", 'LONG LEFT RIGHT ARROW'],
                ["0x27fa", 'LONG LEFT RIGHT DOUBLE ARROW'],
                ["0x2600", 'sun'],
                ["0x2601", 'cloud']
            ],

            // responsive filemanager
            relative_urls: false,
            document_base_url: "/",
            external_filemanager_path: "/frontend/filemanager/",
            filemanager_title: "Responsive Filemanager",
            external_plugins: {
                "filemanager": "/frontend/filemanager/plugin.js"
            },
            paste_as_text: true,

            templates: [
                {foreach $edit_templates as $template}
                {
                    "title": "{$template.title}",
                    "description": "{$template.desc}",
                    "url": "{$template.url}"
                },
                {/foreach}
            ],

            {if $edit_templates_options}
            template_popup_width: {$edit_templates_options.template_popup_width},
            template_popup_height: {$edit_templates_options.template_popup_height},
            content_css: "{$edit_templates_options.content_css}"
            {/if}
        };

        // add markdown and simple configs

        function tinify(config, elem, mode)
        {
            m = (typeof mode != 'undefined') ? mode : true;
            tinyMCE.settings = config;
            m       ? tinyMCE.execCommand('mceAddEditor', true, elem)
                    : tinyMCE.execCommand('mceRemoveEditor', false, elem);
        }
    </script>
    <script type="text/javascript" id="init">
        let saving_in_progress = false;
        $(document).ready(function(){
            tinify(tiny_config, 'edit-textarea');

            setTimeout(function(){
                $('input#title').focus()
            }, 300);

            $("#actor-back").on('click', function(){
                document.location.href = '{$html_callback}';
            });
            // Аякс-обработчик сохранения, спиннер, вывод результата. Не забываем на время обработки дизейблить кнопки, а при ошибке - энэйблить.
            $("#form-edit-region").on('submit', function(){
                let url = $(this).attr('action');
                if (saving_in_progress) return false;

                $.ajax({
                    async       : false,
                    cache       : false,
                    type        : 'POST',
                    url         : url,
                    dataType    : 'json',
                    data        : $(this).serialize(),
                    beforeSend  : function(){
                        saving_in_progress = true;
                        $("#ajax-process").show();

                        // disable buttons
                        $("#actor-submit").prop('disabled', true);
                        $("#actor-back").prop('disabled', true);
                    },
                    success     : function(answer){
                        if (answer['is_success']) {
                            $("#ajax-result").show();
                            setTimeout(function(){
                                window.location.replace("{$html_callback}")
                            }, window.editor_config.success_edit_timeout);
                        } else {
                            saving_in_progress = false;
                            $("#ajax-result").show().html( answer['message'] );
                            // enable buttons
                            $("#actor-submit").removeAttr('disabled');
                            $("#actor-back").removeAttr('disabled');
                        }
                    }
                });
                return false;
            });

        });
    </script>
</head>
<body>
<h3 style="margin-bottom: 1px">Регион:<span style="color: blue">  {if $is_present}{$region_title}{else}{$id_region}{/if}</span></h3>

<form action="{$form_actor}" method="post" id="form-edit-region">
    <input type="hidden" name="edit:id:map"     value="{$id_map}">
    <input type="hidden" name="edit:id:region"  value="{$id_region}">
    <input type="hidden" name="edit:alias:map"  value="{$alias_map}">
    <input type="hidden" name="edit:html_callback" value="{$html_callback}" />
    <input type="hidden" name="edit:layout:type" value="svg">

    <table width="100%" style="text-align: left" border="1">
        <tr>
            <td>
                <fieldset class="fields_area">
                    <div class="field">
                        <label for="title">Название региона:</label> <br>
                        <input type="text" name="edit:region:title" id="title" size="90" value="{$region_title}" tabindex="1" required />
                        <span class="mark-required">*</span>
                    </div>
                </fieldset>
            </td>
            <td>
                <label tabindex="2">
                    В списках:
                    <select name="edit:is:excludelists">
                        <option value="N"{if $is_exludelists eq "N"} selected{/if}>Во всех</option>
                        <option value="F"{if $is_exludelists eq "F"} selected{/if}>Только слоя</option>
                        <option value="A"{if $is_exludelists eq "A"} selected{/if}>Нигде</option>
                    </select>
                </label>
            </td>
            <td>
                <label tabindex="3">
                    Видимость:
                    <select name="edit:is:publicity">
                        <option value="ANYONE"{if $is_publicity eq "ANYONE"} selected{/if}>Всем</option>
                        <option value="VISITOR"{if $is_publicity eq "VISITOR"} selected{/if}>Участникам</option>
                        <option value="EDITOR"{if $is_publicity eq "EDITOR"} selected{/if}>Редакторам</option>
                        <option value="OWNER"{if $is_publicity eq "OWNER"} selected{/if}>Владельцу</option>
                    </select>
                </label>
            </td>
        </tr>
    </table>



    <label for="edit-textarea" class="label_textarea label_fullwidth">
        <textarea name="edit:region:content" id="edit-textarea" cols="10" tabindex="4">{$region_text}</textarea>
    </label>
    <fieldset class="fields_area">
        <div class="field">
            <label for="edit-restricted">Сообщение при недоступности региона:
                <input type="text" name="edit:region:content_restricted" size="120" value="{$region_restricted}" id="edit-restricted" tabindex="5" autocomplete="off"/>
            </label>
        </div>
        <hr />
        <div class="field">
            <label for="edit-reason">Комментарий редактора:
                <input type="text" name="edit:region:comment" size="90" value="" id="edit-reason" tabindex="6" autocomplete="off"/>
            </label>
        </div>
    </fieldset>
    <div class="clear"></div>

    <fieldset>
        <div class="label_fullwidth">
            <button type="submit" id="actor-submit" tabindex="7" style="color: limegreen">Сохранить</button>
            <span id="ajax-process" style="display: none">
                Сохраняю... &nbsp;
                <img src="/frontend/images.spinners/21.svg" height="18" alt="ready"/>
            </span>
            <span id="ajax-result" style="display: none;">Сохранение успешно! Через несколько секунд возвращаемся на карту.</span>
            <button type="button" id="actor-back" style="float:right; color: red" tabindex="8">Назад на карту</button>
        </div>
    </fieldset>
</form>
{if !empty($region_revisions)}
    <fieldset id="revisions_fieldset">
        <ul>
            {foreach $region_revisions as $region}
                <li>
                    {$region.edit_name} edited `{$region.title}` at {$region.edit_date} from IP {$region.ipv4}
                    {if !empty($region.edit_comment)}
                        <small>{$region.edit_comment}</small>
                    {/if}
                </li>
            {/foreach}
        </ul>
    </fieldset>
{/if}
<div class="clear"></div>
<hr>
<small style="float: left">Logged as <strong>{$is_logged_user}</strong> from <strong>{$is_logged_user_ip}</strong></small>
<small style="float: right"><em>{$copyright}</em></small>
</body>


</html>
