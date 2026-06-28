<div class="auth-card">
    <h1>Восстановление пароля</h1>
    <p>Введите email, привязанный к аккаунту</p>

    <form class="auth-form" method="post" action="/auth/recover">
        <div class="field">
            <label for="recover-email">E-Mail</label>
            <input type="email" id="recover-email" name="email" placeholder="your@email.com" value="" required tabindex="1" autofocus>
        </div>

        <div class="field">
            <button type="submit" class="btn-submit" tabindex="2">Отправить ссылку для сброса</button>
        </div>

        <div class="field-actions">
            <a href="{Arris\AppRouter::getRouter('view.form.login')}" tabindex="3">Вспомнили пароль? Войти</a>
        </div>
    </form>
</div>
