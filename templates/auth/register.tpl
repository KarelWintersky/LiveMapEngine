<div class="auth-card">
    {if $_config.auth.is_logged_in}
        <div class="auth-already">
            <p>Вы уже вошли как <strong>{$_config.auth.username} ({$_config.auth.email})</strong></p>
            <p>Чтобы зарегистрировать новый аккаунт, сначала выйдите из текущего.</p>
            <a href="/" class="btn-back">Перейти на главную</a>
        </div>
    {else}
        <h1>Регистрация</h1>
        <p>Создайте новый аккаунт</p>

        <form class="auth-form" method="post" action="{Arris\AppRouter::getRouter('callback.form.register')}">
            <input type="hidden" name="action" value="auth">

            <div class="field">
                <label for="reg-email">E-Mail</label>
                <input type="email" id="reg-email" name="email" placeholder="your@email.com" value="" required tabindex="1" autofocus>
            </div>

            <div class="field">
                <label for="reg-username">Имя пользователя</label>
                <input type="text" id="reg-username" name="username" placeholder="Имя" value="" required tabindex="2">
            </div>

            <div class="field">
                <label for="reg-password">Пароль</label>
                <input type="password" id="reg-password" name="password" placeholder="••••••••" value="" required tabindex="3">
            </div>

            <div class="field">
                <label for="reg-password-retry">Повторно пароль</label>
                <input type="password" id="reg-password-retry" name="password_retry" placeholder="••••••••" value="" required tabindex="4">
            </div>

            <div class="field">
                <label>Капча</label>
                <div class="captcha-row">
                    <a href="javascript:void(0);" onclick="$('#reg-captcha-img').attr('src', '/kcaptcha.php?sid={$sid}&r='+Math.random()); return false;" title="Обновить изображение">
                        <img src="/kcaptcha.php?sid={$sid}" id="reg-captcha-img" alt="captcha">
                    </a>
                    <div class="captcha-input">
                        <input type="text" name="captcha" placeholder="Код с картинки" tabindex="5">
                    </div>
                </div>
                <a class="captcha-refresh" href="javascript:void(0);" onclick="$('#reg-captcha-img').attr('src', '/kcaptcha.php?sid={$sid}&r='+Math.random()); return false;" tabindex="-1">обновить изображение</a>
            </div>

            <div class="field">
                <button type="submit" class="btn-submit" tabindex="6">Зарегистрироваться</button>
            </div>

            <div class="field-actions">
                <a href="{Arris\AppRouter::getRouter('view.form.login')}" tabindex="7">Уже есть аккаунт? Войти</a>
            </div>
        </form>
    {/if}
</div>
