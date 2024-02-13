<!DOCTYPE html>
<html lang="ru">
<head>
    <title>Livemap :: Auth</title>

    <script src="/frontend/jquery/jquery-3.2.1_min.js"></script>
    <style>
        .content-center {
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            padding-top: 10%;
            text-align: center;
        }
        .left-align {
            text-align: left;
        }
        input[required] {
            border: 1px solid teal ;
            border-radius: 5px;
        }
    </style>
    <script>
        const flash_messages = {$flash_messages};
        $(document).ready(function() {
            // notifyFlashMessages(flash_messages);

            $("[data-action='redirect']").on('click', function (event) {
                let url = $(this).data('url');
                let target = $(this).data('target');

                if (target == "_blank") {
                    window.open(url, '_blank').focus();
                } else {
                    window.location.href = url;
                }
            });
        });
    </script>
</head>
<body>
<div class="content-center">
    <div>
        {if $_config.auth.is_logged_in}
        Вы уже залогинены <br><br> <strong>{$_config.auth.username} ({$_config.auth.email})<strong> <br><br>

        <button
                type="button"
                data-action="redirect"
                data-url="/"
                style="font-size: large">Перейти на главную</button>

        {else}

        <form method="post" action="{Arris\AppRouter::getRouter('callback.form.login')}" class="left-align">
            <input type="hidden" name="action" value="auth" />
            <table>
                <tr>
                    <td>E-Mail: </td>
                    <td><input type="text" placeholder="E-Mail" name="email" value="" required tabindex="1" autofocus/></td>
                </tr>
                <tr>
                    <td>Password:&nbsp;&nbsp;&nbsp;</td>
                    <td><input type="password" placeholder="пароль" name="password" value="" required tabindex="2" /></td>
                </tr>
                <tr>
                    <td colspan="2">
                        <br>
                        <input type="submit" value="Login >>>" tabindex="3">
                    </td>
                </tr>
            </table>
        </form>
        {/if}
    </div>
</div>
</body>
</html>



