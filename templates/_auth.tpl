{* контейнер страниц авторизации *}
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{$title}</title>

    <link rel="stylesheet" href="/frontend/main.css">

    <style>
        .auth-page {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 100px 24px 64px;
            flex: 1;
        }

        .auth-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06), 0 2px 12px rgba(0,0,0,0.04);
            padding: 40px 36px;
            width: 100%;
            max-width: 420px;
        }

        .auth-card h1 {
            font-weight: 500;
            font-size: 1.375rem;
            color: #111;
            margin-bottom: 4px;
        }

        .auth-card p {
            font-size: 0.875rem;
            color: #888;
            margin-bottom: 24px;
        }

        .auth-form .field {
            margin-bottom: 16px;
        }

        .auth-form label {
            display: block;
            font-size: 0.8rem;
            font-weight: 500;
            color: #444;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .auth-form input[type="text"],
        .auth-form input[type="email"],
        .auth-form input[type="password"] {
            width: 100%;
            padding: 10px 14px;
            font-size: 0.95rem;
            font-family: inherit;
            border: 1px solid #ddd;
            border-radius: 6px;
            background: #fafafa;
            color: #222;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }

        .auth-form input[type="text"]:focus,
        .auth-form input[type="email"]:focus,
        .auth-form input[type="password"]:focus {
            border-color: #111;
            box-shadow: 0 0 0 3px rgba(0,0,0,0.06);
            background: #fff;
        }

        .auth-form .btn-submit {
            width: 100%;
            padding: 12px;
            font-size: 0.95rem;
            font-family: inherit;
            font-weight: 500;
            color: #fff;
            background: #111;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 4px;
        }

        .auth-form .btn-submit:hover {
            background: #333;
        }

        .auth-form .btn-submit:active {
            background: #000;
        }

        .auth-form .captcha-row {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .auth-form .captcha-row img {
            border-radius: 6px;
            border: 1px solid #eee;
        }

        .auth-form .captcha-row .captcha-input {
            flex: 1;
            min-width: 100px;
        }

        .auth-form .captcha-refresh {
            font-size: 0.8rem;
            color: #888;
            cursor: pointer;
            transition: color 0.2s;
        }

        .auth-form .captcha-refresh:hover {
            color: #111;
        }

        .auth-form .field-actions,
        .field-actions {
            text-align: center;
            margin-top: 16px;
        }

        .auth-form .field-actions a {
            font-size: 0.85rem;
            color: #888;
            transition: color 0.2s;
        }

        .auth-form .field-actions a:hover {
            color: #111;
        }

        .auth-already {
            text-align: center;
            font-size: 0.95rem;
            color: #555;
        }

        .auth-already strong {
            color: #111;
        }

        .auth-already .btn-back {
            display: inline-block;
            margin-top: 16px;
            padding: 10px 28px;
            font-size: 0.9rem;
            font-weight: 500;
            color: #fff;
            background: #111;
            border-radius: 6px;
            transition: background 0.2s;
        }

        .auth-already .btn-back:hover {
            background: #333;
        }

        @media (max-width: 767px) {
            .auth-page {
                padding: 80px 16px 48px;
            }
            .auth-card {
                padding: 28px 20px;
            }
        }
    </style>

    <script src="/frontend/jquery/jquery-3.2.1_min.js"></script>
    <script>
        const flash_messages = {$flash_messages};
    </script>
    <script defer src="/frontend/main.js"></script>
</head>
<body>

<div class="bg-compass" aria-hidden="true">
    <svg width="100%" height="100%" viewBox="0 0 200 200" preserveAspectRatio="xMidYMid meet" xmlns="http://www.w3.org/2000/svg">
        <rect width="200" height="200" fill="none"/>
        <path d="m100 11 4 7h-8z"/>
        <circle cx="100" cy="100" r="72" fill="none" stroke="#000" stroke-width=".5"/>
        <g id="d"><g id="g"><g id="e"><path d="m172 100-6-3v6z"/><path d="m100 100h67" fill="none" stroke="#000"/><rect x="159.5" y="95" width="5.5" height="10" fill="#fff" stroke="#000" stroke-width=".1"/></g><use transform="rotate(11.25 100 100)" xlink:href="#e"/><use transform="rotate(22.5 100 100)" xlink:href="#e"/><use transform="rotate(33.75 100 100)" xlink:href="#e"/></g><use transform="rotate(45 100 100)" xlink:href="#g"/></g>
        <use transform="rotate(90 100 100)" xlink:href="#d"/><use transform="rotate(180 100 100)" xlink:href="#d"/><use transform="rotate(-90 100 100)" xlink:href="#d"/>
        <path d="m100 28-8 15h16z"/><path d="m100 43v52" stroke="#000" stroke-width="3"/>
        <g id="b"><g id="c"><path d="m100 5.297v-3.287" fill="none" stroke="#000"/><path d="m101.7 5.3.06-3.25m1.6 3.3.1-3.25m1.52 3.35.2-3.3m1.46 3.4.24-3.3m1.44 3.41.26-3.27m1.36 3.41.37-3.25m1.28 3.45.4-3.25m1.24 3.45.45-3.21m1.186 3.473.5-3.2" fill="none" stroke="#000" stroke-width=".1"/></g><use transform="rotate(10 100 100)" xlink:href="#c"/><use transform="rotate(20 100 100)" xlink:href="#c"/><use transform="rotate(30 100 100)" xlink:href="#c"/><use transform="rotate(40 100 100)" xlink:href="#c"/><use transform="rotate(50 100 100)" xlink:href="#c"/></g>
        <use transform="rotate(60 100 100)" xlink:href="#b"/><use transform="rotate(120 100 100)" xlink:href="#b"/><use transform="rotate(180 100 100)" xlink:href="#b"/><use transform="rotate(240 100 100)" xlink:href="#b"/><use transform="rotate(-60 100 100)" xlink:href="#b"/>
        <g id="a"><path id="f" d="m180.8 114.2 6.5 3.3-7.3.6m.8-3.9-.8 3.9-9.8-2 .8-3.9z" fill="#fff" stroke="#000" stroke-width=".1"/><use transform="rotate(11.25 99 100)" xlink:href="#f"/><use transform="rotate(22.5 99 100)" xlink:href="#f"/><path d="m182 104h-10v-8h10m7 4-7 4v-8z" fill="none" stroke="#000" stroke-width=".1"/></g>
        <circle cx="100" cy="100" r="5" fill="#fff"/><use transform="rotate(45 100.3 100.5)" xlink:href="#a"/><use transform="rotate(90 100 100)" xlink:href="#a"/><use transform="rotate(135 100 100)" xlink:href="#a"/><use transform="rotate(180 100 100)" xlink:href="#a"/><use transform="rotate(225 100 100)" xlink:href="#a"/><use transform="rotate(-90 100 100)" xlink:href="#a"/><use transform="rotate(-45 100 100)" xlink:href="#a"/>
        <g stroke="#000"><circle cx="100" cy="100" r="98" fill="none" stroke-width=".5"/><circle cx="100" cy="100" r="94.7" fill="none" stroke-width=".5"/><path d="m100 96v8m-4-4h8" fill="#fff" stroke-dasharray=".5" stroke-width=".3"/></g>
    </svg>
</div>

<header class="top-bar" id="top-bar">
    <div class="top-bar-inner">
        <a href="/" class="top-bar-logo">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76" fill="rgba(255,255,255,0.15)"/>
                <line x1="12" y1="2" x2="12" y2="6"/><line x1="12" y1="18" x2="12" y2="22"/>
                <line x1="2" y1="12" x2="6" y2="12"/><line x1="18" y1="12" x2="22" y2="12"/>
            </svg>
            StoryMaps
        </a>
        <nav class="top-bar-nav" id="top-bar-nav">
            <a href="/" class="nav-link">На главную</a>
        </nav>
        <button class="top-bar-burger" id="top-bar-burger" aria-label="Меню">
            <span></span><span></span><span></span>
        </button>
    </div>
</header>

<main class="auth-page">
    {include file=$inner_template}
</main>

<footer class="site-footer">
    <div class="footer-inner">
        <div class="footer-copyright">
            {if isset($_config.app.copyright)}
                <p>{$_config.app.copyright}</p>
            {else}
                <p>StoryMaps Engine &copy; Karel Wintersky</p>
            {/if}
        </div>
        <div class="footer-links">
            <a href="https://github.com/karelwintersky">GitHub</a>
            <span class="footer-sep">·</span>
            <a href="mailto:karel.wintersky@gmail.com">Контакты</a>
        </div>
    </div>
</footer>

<script>
    $(document).ready(function() {
        notifyFlashMessages(flash_messages);
    });
</script>

</body>
</html>
