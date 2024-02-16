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



