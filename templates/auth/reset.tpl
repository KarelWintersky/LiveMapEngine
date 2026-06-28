<div class="auth-card">
    <h1>Новый пароль</h1>
    <p>Придумайте новый пароль для вашего аккаунта</p>

    <form class="auth-form" method="post" action="/auth/setnewpassword">
        <div class="field">
            <label for="reset-password">Новый пароль</label>
            <input type="text" id="reset-password" name="password" placeholder="новый пароль" value="" required tabindex="1" autofocus>
        </div>

        <div class="field">
            <button type="submit" class="btn-submit" tabindex="2">OK</button>
        </div>
    </form>
</div>
