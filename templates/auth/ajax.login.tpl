<div style="display: block; padding-left: 2em;">
    <form action="/auth/ajax:login" method="post" id="form-login">
        <table width="40%">
            <tr>
                <td>Login: </td>
                <td><input type="text" size="30" name="auth:data:login" tabindex="1" id="af-login" autofocus value="{$last_login}"></td>
            </tr>
            <tr>
                <td>Password: </td>
                <td><input type="password" size="30" id="af-password" name="auth:data:password" tabindex="2"></td>
            </tr>
            <tr>
                <td> Remember: </td>
                <td><input type="checkbox" id="log_in_remember_me" tabindex="3" name="auth:data:remember_me" value="1" checked> </td>
            </tr>
            <tr>
                <td></td>
                <td><input type="submit" name="auth:action:login" value="Login" tabindex="4"></td>
            </tr>
            <tr>
                <td colspan="2"><span id="auth_result"></span></td>
            </tr>
        </table>
    </form>
</div>
<script type="text/javascript">
    $(function () {
        // set focus to first empty field
        var target = $('#af-login').val() != '' ? '#af-password' : '#af-login';
        setTimeout(function () {
            $(target).focus();
        }, 1000);
    });

    $("#form-login input[type=submit]").on('click', function(){
        var url = $("#form-login").attr('action');
        var redirect_timeout = 1000;

        $.ajax({
            async: false,
            cache: false,
            type: 'POST',
            url: url,
            dataType : 'json',
            data: $("#form-login").serialize(),
            beforeSend: function(){
                $("#auth_result").html('').html("Checking credentials...");
            },
            success: function(result) {
                if (!result.error) {
                    $("#auth_result").html('').html( result.error_messages + ' : Wait ' + (redirect_timeout / 1000) + ' sec...' );

                    //@todo: сделать таймер редиректа
                    setTimeout(function(){
                        window.location.href = '/';
                    }, redirect_timeout);
                    // no error
                } else {
                    $("#auth_result").html('').html( result.error_messages );
                    // error
                }
            }
        });
        return false;
        // дёргаем аякс. В зависимости от ответа - пишем сообщение об ошибке либо пишем сообщение об успехе и делаем редирект на корень через 3 секунды
    });
</script>