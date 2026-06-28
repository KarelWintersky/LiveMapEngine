<div class="auth-card">
    {if $_config.auth.is_logged_in}
        <div class="auth-already">
            <p>Вы уже вошли как <strong>{$_config.auth.username} ({$_config.auth.email})</strong></p>
            <a href="/" class="btn-back">Перейти на главную</a>
        </div>
    {else}
        <h1>Вход</h1>
        <p>Войдите в свой аккаунт</p>

        <form class="auth-form" method="post" action="{Arris\AppRouter::getRouter('callback.form.login')}">
            <input type="hidden" name="action" value="auth">

            <div class="field">
                <label for="login-email">E-Mail</label>
                <input type="email" id="login-email" name="email" placeholder="your@email.com" value="" required tabindex="1" autofocus>
            </div>

            <div class="field">
                <label for="login-password">Пароль</label>
                <input type="password" id="login-password" name="password" placeholder="••••••••" value="" required tabindex="2">
            </div>

            <div class="field">
                <button type="submit" class="btn-submit" tabindex="3">Войти</button>
            </div>

            <div class="field-actions">
                <a href="{Arris\AppRouter::getRouter('view.auth.recover.form')}" tabindex="4">Забыли пароль?</a>
            </div>

            <div class="field-actions" style="margin-top: 8px; padding-top: 16px; border-top: 1px solid #eee;">
                <span style="font-size: 0.85rem; color: #888;">Нет аккаунта?</span>
                <a href="{Arris\AppRouter::getRouter('view.form.register')}" style="font-size: 0.85rem; font-weight: 500; color: #111; margin-left: 4px;">Зарегистрироваться</a>
            </div>
        </form>
    {/if}
</div>
