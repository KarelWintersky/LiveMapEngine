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
    <script type="text/javascript" id="bind-auth">
        $('#actor-auth').on('click', function(){
            $.colorbox({
                href: '/auth',
                width: '30%',
                height: '30%',
                title: 'Login form'
            });
        });
    </script>
</head>
<body>

<div class="wrapper">
    <!-- Sidebar Holder -->
    <nav id="sidebar">
        <div class="sidebar-header">
            <h3>Интерактивные карты</h3>
        </div>
        {*<ul class="list-unstyled components">
            <li>
                <a aria-expanded="#">Действия</a>
                <ul class="list-unstyled" id="pageActionsSubmenu">
                    {if !$authinfo.is_logged}
                        <li>
                            <a id="actor-auth" href="#">Вход</a>
                        </li>
                        <li>
                            <a href="/auth/register">Регистрация</a>
                        </li>
                    {else}
                        <li>
                            <a href="/auth/profile">Настройки аккаунта</a>
                        </li>
                        <li>
                            <a id="actor-auth" old-href="/auth/logout">Выход</a>
                        </li>
                    {/if}
                </ul>
            </li>
        </ul>*}

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


        <small>
            {*{?*authinfo.is_logged*}{**}{*authinfo.email*}{* <br/> (*}{*authinfo.ip*}{*) {?}*}
        </small>
    </nav>

    <!-- Page Content Holder -->
    <div id="content">
        <iframe src="/map:iframe/pony?view=iframe" width="100%" height="500px" scrolling="no" frameborder="1">
        </iframe>
        <div class="line"></div>

        <h2 id="maps">Карты</h2>
        <p>
            <ul class="components">
                {foreach $maps_list as $map}
                    <li>
                        <a href="/map/{$map.alias}">{$map.title}</a>
                    </li>
                {/foreach}
            </ul>
        </p>


        <div class="line"></div>

    </div>
</div>

<div style="display:none">
    <div id="colorboxed-view" style="padding:10px; background:#fff;">
        <div id="colorboxed-view-content"></div>
    </div>
</div>


</body>
</html>
