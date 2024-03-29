<br/>
<div style="display: block; padding-left: 2em;">
    You are logged in as <strong>{$is_logged_user}</strong> from <strong>{$is_logged_user_ip}</strong>
    <br/>
    <form action="/auth/action:logout" method="post" id="form-logout">
        <table width="40%">
            <tr>
                <td colspan="2"> <input type="submit" name="auth:action:logout" value="Logout"> </td>
            </tr>
        </table>
    </form>
</div>

<script type="text/javascript">
    $("#form-logout input[type=submit]").on('click', function(){
        var url = $("#form-logout").attr('action');
        var redirect_timeout = 1;

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
                    $("#auth_result").html('').html( result.error_messages + ' : Redirecting in ' + (redirect_timeout / 1000) + ' sec...' );
                    setTimeout(function(){
                        window.location.href = '/';
                    }, redirect_timeout);
                } else {
                    $("#auth_result").html('').html( result.error_messages );
                }
            }
        });
        return false;
    });
</script>