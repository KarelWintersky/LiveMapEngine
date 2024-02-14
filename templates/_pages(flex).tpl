{* контейнер публичных страниц, исключая карты, V2 *}
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Живые карты</title>

    {include file="_common/favicon_defs.tpl"}

    <script src="/frontend/jquery/jquery-1.12.0.min.js"></script>

    <script src="/frontend/scripts.js"></script>
    <script>
        const flash_messages = {$flash_messages};
        $(document).ready(function () {
            notifyFlashMessages(flash_messages);
        });
    </script>
    <style>
        :root {
            --color-legacy: #7386D5;
            --color-submenu: #6d7fcc;
            --color-hover: #20B2AA;
        }

        @font-face {
            font-family: 'Poppins';
            font-style: normal;
            font-weight: 400;
            src: local(''),
            url('/frontend/fonts/poppins-v15-latin-regular.woff2') format('woff2'),
            url('/frontend/fonts/poppins-v15-latin-regular.woff') format('woff');
        }

        @font-face {
            font-family: 'Poppins';
            font-style: normal;
            font-weight: 500;
            src: local(''),
            url('/frontend/fonts/poppins-v15-latin-500.woff2') format('woff2'),
            url('/frontend/fonts/poppins-v15-latin-500.woff') format('woff');
        }

        * {
            box-sizing: border-box;
            padding: 0;
            margin: 0;
        }

        body, html {
            height: 100%;
            font-family: 'Poppins', sans-serif;
        }

        a {
            text-decoration: none;
        }

        #wrapper {
            margin: auto;
            height: 100%;
        }

        #header {
            height: 50px;
            padding: 20px;
            text-align: center;
        }

        #container {
            display: flex;
        }

        #content {
            width: 100%;
            height: 700px;
            padding: 20px;
        }

        #sidebar {
            width: 250px;
            margin-right: 20px;
            padding: 5px;
            background-color: var(--color-legacy);
        }

        #sidebar h3 {
            color: whitesmoke;
            font-size: 1em;
        }

        #footer {
            height: 100px;
            padding: 20px;
            font-size: small;
        }

        .menu {
            list-style-type: none;
            padding: 0;
        }

        .menu li {
            padding: 15px;
            font-size: 0.9em;
        }

        .menu li a {
            color: whitesmoke;
            display: block;
            width: 100%;
        }

        .menu li:hover {
            cursor: pointer;
            background-color: whitesmoke;
        }

        .menu li:hover * {
            color: var(--color-legacy);
        }

        iframe {
            width: 100%;
            height: 500px;
        }

        #content a, #content a:visited {
            color: #0d88c1;
        }

        #content a:hover {
            text-decoration: underline;
        }

        .line {
            width: 100%;
            height: 1px;
            border-bottom: 1px dashed #ddd;
            margin: 40px 0;
        }
    </style>
</head>
<body>

<div id="wrapper">
    {*<div id="header">
        Livemap Atlas
    </div>*}
    <div id="container">
        <div id="sidebar">
            <h3>Карты</h3>
            <ul class="menu">
                {foreach $maps_list as $map}
                    <li class="submenu-item">
                        <a href="/map/{$map.alias}">{$map.title}</a>
                    </li>
                {/foreach}
            </ul>

            <hr>

            <ul class="menu">
                {if $is_logged_in}
                    <li class="submenu-item">
                        <a href="/user/profile">Мой профиль</a>
                    </li>
                    {if $_config.auth.is_admin}
                        <li class="submenu-item">
                            <a href="{Arris\AppRouter::getRouter('admin.view.main.page')}">[[[ Админка ]]]</a>
                        </li>
                    {/if}
                    <li class="submenu-item">
                        <a href="{Arris\AppRouter::getRouter('view.form.logout')}">Выход</a>
                    </li>
                {else}
                    <li class="submenu-item">
                        <a href="{Arris\AppRouter::getRouter('view.form.login')}">Вход</a>
                    </li>
                    <li class="submenu-item">
                        <a href="{Arris\AppRouter::getRouter('view.form.register')}">Регистрация</a>
                    </li>
                {/if}
            </ul>

        </div>
        <div id="content">
            {include file=$inner_template}
        </div>
    </div>
    {*<div id="footer">
        (c)
    </div>*}
</div>

</body>
</html>


