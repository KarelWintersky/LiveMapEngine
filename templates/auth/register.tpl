    <div>
        <h2>Регистрация</h2>
        {if $_config.auth.is_logged_in}
        Вы уже залогинены <br><br> <strong>{$_config.auth.username} ({$_config.auth.email})<strong> <br><br>
        Прежде чем регистрироваться, нужно сначала выйти из системы.<br><br>
                <button
                        type="button"
                        data-action="redirect"
                        data-url="/"
                        style="font-size: large">Перейти на главную</button>

        {else}
            <form method="post" action="{Arris\AppRouter::getRouter('callback.form.register')}" class="left-align">
                <input type="hidden" name="action" value="auth" />
                <table>
                    <tr>
                        <td>E-Mail: </td>
                        <td><input type="text" placeholder="E-Mail" name="email" value="" required tabindex="1" autofocus></td>
                    </tr>
                    <tr>
                        <td>Имя пользователя: </td>
                        <td><input type="text" placeholder="Имя" name="username" value="" required tabindex="2"></td>
                    </tr>
                    <tr>
                        <td>Пароль:&nbsp;&nbsp;&nbsp;

                        </td>
                        <td>
                            <input type="password" placeholder="пароль" name="password" value="" required tabindex="3">
                        </td>
                    </tr>
                    <tr>
                        <td>Повторно пароль:&nbsp;&nbsp;&nbsp;</td>
                        <td><input type="password" placeholder="пароль" name="password_retry" value="" required tabindex="4"></td>
                    </tr>
                    <tr>
                        <td>Капча</td>
                        <td>
                            <br>
                            Что написано на картинке? (<a href="javascript:void(0);" onclick="$('#captcha').attr('src', '/kcaptcha.php?sid={$sid}&r='+Math.random()); return false;" title="Обновить изображение">обновить</a>)
                            <br>
                            <a href="javascript:void(0);" onclick="$('#captcha').attr('src', '/kcaptcha.php?sid={$sid}&r='+Math.random()); return false;" title="Обновить изображение"><img src="/kcaptcha.php?sid={$sid}" id="captcha" alt="captcha" ></a>
                            <br>
                            <input type="text" name="captcha" class="small" id="captcha" tabindex="5" style="width: 120px; display: inline-block;" >
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <br>
                            <input type="submit" value="Регистрация" tabindex="6">
                        </td>
                    </tr>
                    <tr>
                        <td></td>
                        <td>
                            <a href="{Arris\AppRouter::getRouter('view.auth.recover.form')}">Восстановить пароль</a>
                        </td>
                    </tr>
                </table>
            </form>
        {/if}
    </div>



