{* контейнер публичных страниц, исключая карты *}
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="STORYMAPS - Карты и истории">
    <title>STORYMAPS - Карты и истории</title>

    {include file="_common/favicon_defs.tpl"}
    {include file="_common/opengraph.tpl"}

    <link rel="stylesheet" href="/frontend/colorbox/colorbox.css">

    <link rel="stylesheet" href="/frontend/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="/frontend/bootstrap/with_simple_sidebar.css">

    <script src="/frontend/jquery/jquery-1.12.0.min.js"></script>

    <script src="/frontend/bootstrap/bootstrap.min.js"></script>
    <script src="/frontend/colorbox/jquery.colorbox-min.js"></script>

    <script src="/frontend/scripts.js"></script>
    <script>
        const flash_messages = {$flash_messages};
        $(document).ready(function() {
            notifyFlashMessages(flash_messages);
        });
    </script>
</head>
<body>

<div class="wrapper">
    <nav id="sidebar">

        <ul class="list-unstyled">
            <li>
                <a aria-expanded="#">Карты</a>
                <ul class="list-unstyled">
                    {foreach $maps_list as $map}
                        <li>
                            <a href="/map/{$map.alias}">{$map.title}</a>
                        </li>
                    {/foreach}
                </ul>

            </li>
        </ul>
        <hr>
        <ul class="list-unstyled">
            {if $is_logged_in}
                <li>
                    <a href="{Arris\AppRouter::getRouter('view.user.profile')}?id={$_auth.id}">Мой профиль</a>
                </li>
                {if $_config.auth.is_admin}
                    <li>
                        <hr>
                    </li>
                    <li>
                        <a href="{Arris\AppRouter::getRouter('admin.main.page')}">[[[ Админка ]]]</a>
                    </li>
                    <li>
                        <hr>
                    </li>
                {/if}
                <li>
                    <a href="{Arris\AppRouter::getRouter('view.form.logout')}">Выход</a>
                </li>
            {else}
                <li>
                    <a href="{Arris\AppRouter::getRouter('view.form.login')}">Вход</a>
                </li>
                <li>
                    <a href="{Arris\AppRouter::getRouter('view.form.register')}">Регистрация</a>
                </li>
            {/if}
        </ul>
    </nav>

    <div id="content">
        {include file=$inner_template}
    </div>
</div>

</body>
</html>
