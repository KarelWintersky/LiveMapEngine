<!DOCTYPE html>
<html lang="ru">
<head>
    <title>Живые карты -- настройки профиля пользователя</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">

    {include file="_common/favicon_defs.tpl"}

    <style type="text/css">
        body {
            font-size:14px;
        }
        form {
            float:left;
        }
        .field {
            clear:both; text-align:right; line-height:25px;
        }
        label {
            float:left; padding-right:10px;
        }
    </style>
</head>
<body>
<h2>Настройки профиля пользователя</h2>

<fieldset>
    <legend>Редактирование профиля</legend>
    <form method="post" action="/auth/action:profile.update">
        <div class="field">
            <label for="auth_editprofile_name">Меня зовут: </label>
            <input type="text" size="15" id="auth_editprofile_name" name="auth:editprofile:name" value="{$username}">
        </div>
        <div>
            <label for="auth_editprofile_gender">Я: </label>
            <select id="auth_editprofile_gender" name="auth:editprofile:gender">
                <option value="M"{if $gender eq "M"} selected{/if}>кавалер</option>
                <option value="F"{if $gender eq "F"} selected{/if}>дама</option>
                <option value="N"{if $gender eq "N"} selected{/if}>существо</option>
            </select>
        </div>
        <div>
            <label for="auth_editprofile_city">Мой город:</label>
            <input type="text" size="25" id="auth_editprofile_city" name="auth:editprofile:reg_city" value="{$city}">
        </div>
        <div class="field">
            <label for="auth_editprofile_password">мой пароль: </label>
            <input type="password" size="15" id="auth_editprofile_password" name="auth:editprpfile:password">
        </div>
        <div class="field">
            <label for="auth_editprofile_submit"></label>
            <button name="auth:editprofile:submit" id="auth_editprofile_submit" value="update_personal_data">Сохранить</button>
        </div>
    </form>
</fieldset>

<fieldset>
    <legend>Участие в проектах</legend>
    Список проектов, в которых пользователь участвует (с указанием статуса).
    Варианты:
    - наблюдатель: покинуть
    - редактор: покинуть
    - владелец: ничего сделать нельзя, пока не удален проект (удаляется со страницы настроек проекта)
</fieldset>

<fieldset>
    <legend>Сменить регистрационный e-mail</legend>
    <form method="post" action="/auth/action:profile.update">
        <div class="field">
            <label for="auth_changeemail_current">Текущий E-Mail: </label>
            <input type="email" size="15" id="auth_changeemail_current" disabled value="{$current_email}">
        </div>
        <div class="field">
            <label for="auth_changeemail_newemail">Новый E-Mail: </label>
            <input type="email" size="15" id="auth_changeemail_newemail" name="auth:changeemail:new" required>
        </div>
        <div class="field">
            <label for="auth_changeemail_password">Мой пароль: </label>
            <input type="password" size="15" id="auth_changeemail_password" name="auth:changeemail:password">
        </div>
        <div class="field">
            <label for="auth_changeemail_submit"></label>
            <button name="auth:editprofile:submit" id="auth_changeemail_submit" value="change_email">Сменить E-Mail</button>
        </div>
    </form>

</fieldset>

<fieldset>
    <legend>Сменить пароль</legend>
    <form method="post" action="/auth/action:profile.update">
        <div class="field">
            <label for="auth_changepassword_current">Текущий пароль: </label>
            <input type="text" size="15" id="auth_changepassword_current" name="auth:changepassword:current">
        </div>
        <div class="field">
            <label for="auth_changepassword_new">Новый пароль: </label>
            <input type="text" size="15" id="auth_changepassword_new" name="auth:changepassword:new">
        </div>
        <div class="field">
            <label for="auth_changepassword_again">Повторите пароль: </label>
            <input type="text" size="15" id="auth_changepassword_again" name="auth:changepassword:again">
        </div>
        {if $strong_password}
        <div class="hint">В пароле должна быть хотя бы 1 цифра, 1 строчная и 1 прописная буква.</div>
        {/if}
        <div class="field">
            <label for="auth_changepassword_submit"></label>
            <button name="auth:editprofile:submit" id="auth_changepassword_submit" value="change_password">Сменить пароль</button>
        </div>
    </form>
</fieldset>

<fieldset>
    <legend>Удаление аккаунта</legend>
    <span style="color:red">Внимание, это действие необратимо! Снимаются все права, аккаунт замораживается.</span>
    <br/>
    <form method="post" action="/auth/action:profile.update">
        <div class="field">
            <label for="auth_currentmail_newemail">Мой E-Mail: </label>
            <input type="text" size="15" id="auth_currentmail_newemail" name="auth:deleteaccount:email">
        </div>
        <div class="field">
            <label for="auth_deleteaccount_password">Мой пароль: </label>
            <input type="text" size="15" id="auth_deleteaccount_password" name="auth:deleteaccount:password">
        </div>
        <div class="field">
            <label for="auth_deleteaccount_submit"></label>
            <button name="auth:editprofile:submit" id="auth_deleteaccount_submit" value="delete_account">Удалить аккаунт</button>
        </div>
    </form>
</fieldset>

<hr>

<a href="/">На стартовую...</a>

</body>
</html>