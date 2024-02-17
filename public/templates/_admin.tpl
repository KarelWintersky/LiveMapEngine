<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="A layout example with a side menu that hides on mobile, just like the Pure website.">
    <title>Responsive Side Menu &ndash; Layout Examples &ndash; Pure</title>
    <link rel="stylesheet" href="/frontend/pure/pure-min.css">
    <link rel="stylesheet" href="/frontend/pure/_styles.admin.css">
</head>
<body>

<div id="layout">
    <a href="#menu" id="menuLink" class="menu-link">
        <span></span>
    </a>

    <div id="menu">
        <div class="pure-menu">
            <a class="pure-menu-heading" href="{Arris\AppRouter::getRouter('admin.main.page')}">LIVEMAP</a>

            {*
            add class `pure-menu-selected` for selected menu
            *}

            <ul class="pure-menu-list">
                <li class="pure-menu-heading menu-item-divided">
                    Пользователи
                </li>

                <li class="pure-menu-item">
                    <a href="{Arris\AppRouter::getRouter('admin.users.view.list')}" class="pure-menu-link">Список</a>
                </li>

                <li class="pure-menu-item">
                    <a href="{Arris\AppRouter::getRouter('admin.users.view.create')}" class="pure-menu-link">Создать</a>
                </li>
                {*<li class="pure-menu-item menu-item-divided">
                    <a href="#" class="pure-menu-link">Services</a>
                </li>

                <li class="pure-menu-item"><a href="#contact" class="pure-menu-link">Contact</a></li>*}
            </ul>
            <ul class="pure-menu-list">
                <li class="pure-menu-heading">
                    Карты
                </li>

                <li class="pure-menu-item">
                    <a href="{Arris\AppRouter::getRouter('admin.maps.view.list')}" class="pure-menu-link">Список</a>
                </li>

                <li class="pure-menu-item">
                    <a href="{Arris\AppRouter::getRouter('admin.maps.view.create')}" class="pure-menu-link">Создать</a>
                </li>

            </ul>
        </div>
    </div>

    <div id="main">
        {include file=$inner_template}
    </div>
</div>

<script src="/frontend/pure/pure.js"></script>

</body>
</html>
