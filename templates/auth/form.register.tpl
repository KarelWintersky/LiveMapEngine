<!DOCTYPE html>
<html lang="ru">
<head>
    <title>Живые карты -- регистрация нового пользователя</title>

    {include file="_common/favicon_defs.tpl"}

    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <style>
        .hint {
            font-size: small; font-style: italic; color: red;
        }
    </style>
    <script type="text/javascript">
        window.onload = function() { document.getElementById("reg_username").focus(); }
    </script>
</head>
<body>

<h2>Регистрация</h2>

<form action="/auth/action:register" method="post">
    <table>
        <tr>
            <td>
                <label for="reg_username">Меня зовут:</label>
            </td>
            <td>
                <input type="text" size="25" id="reg_username" name="register:data:username" required tabindex="1" autofocus>
            </td>
        </tr>
        <tr>
            <td>
                <label for="reg_gender">Я: </label>
            </td>
            <td>
                <select id="reg_gender" name="register:data:gender">
                    <option value="M">кавалер</option>
                    <option value="F">дама</option>
                    <option value="N">существо</option>
                </select>
            </td>
        </tr>
        <tr>
            <td>
                <label for="reg_city">Мой город:</label>
            </td>
            <td>
                <input type="text" size="25" id="reg_city" name="register:data:city">
            </td>
        </tr>
        <tr>
            <td>
                <label for="reg_birthday">Мой день рожденья<br/> (ДД/ММ/ГГГГ):</label>
            </td>
            <td>
                <input type="text" size="25" id="reg_birthday" name="register:data:birthday">
            </td>
        </tr>
        <tr>
            <td>
                <label for="reg_email">E-Mail:</label>
            </td>
            <td>
                <input type="email" size="15" id="reg_email" name="register:data:email">
            </td>
        </tr>
        <tr>
            <td>
                <label for="reg_password">Пароль:</label>
            </td>
            <td>
                <input type="password" size="15" id="reg_password" name="register:data:password"><br/>
            </td>
        </tr>
        <tr>
            <td>
                <label for="reg_password_again">Повторите пароль:</label>
            </td>
            <td>
                <input type="password" size="15" id="reg_password_again" name="register:data:password_again">
            </td>
        </tr>
        {if $strong_password_required}
        <tr>
            <td colspan="2">
                <span class="hint">В пароле должна быть хотя бы 1 цифра, 1 строчная и 1 прописная буква.</span>
            </td>
        </tr>
        {/if}
        <tr>
            <td colspan="2">
                <button name="register:action:doit" value="register">Регистрация >>> </button>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                В настоящий момент регистрация не требует активации аккаунта. Письмо можете не ждать.
            </td>
        </tr>
    </table>
</form>
<hr>
<a href="/">На стартовую...</a>

</body>
</html>