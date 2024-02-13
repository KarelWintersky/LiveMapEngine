<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Живые карты</title>

    {include file="_common/favicon_defs.tpl"}

    <link rel="stylesheet" href="/frontend/colorbox/colorbox.css">

    <link rel="stylesheet" href="/frontend/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="/frontend/bootstrap/with_simple_sidebar.css">

    <script src="/frontend/jquery/jquery-1.12.0.min.js"></script>
    <script src="/frontend/bootstrap/bootstrap.min.js"></script>
    <script src="/frontend/colorbox/jquery.colorbox-min.js"></script>
    <script src="/frontend/scripts.js"></script>
    <script type="text/javascript" id="bind-auth">
        /*$('#actor-auth').on('click', function(){
            $.colorbox({
                href: '/auth',
                width: '30%',
                height: '30%',
                title: 'Login form'
            });
        });*/
    </script>
</head>
<body>

<div class="wrapper">
    <nav id="sidebar">

        <ul class="list-unstyled">
            <li>
                {*<a aria-expanded="#">Карты</a>*}
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
                <li>Кнопки профиля</li>
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
