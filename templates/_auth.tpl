{* контейнер страниц авторизации *}
<!DOCTYPE html>
<html lang="ru">
<head>
    <title>{$title}</title>

    <script src="/frontend/jquery/jquery-3.2.1_min.js"></script>
    <style>
        .content-center {
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            padding-top: 10%;
            text-align: center;
        }
        .left-align {
            text-align: left;
        }
        input[required] {
            border: 1px solid teal ;
            border-radius: 5px;
        }
    </style>
    <script>
        const flash_messages = {$flash_messages};
        $(document).ready(function() {
            notifyFlashMessages(flash_messages);

            $("[data-action='redirect']").on('click', function (event) {
                let url = $(this).data('url');
                let target = $(this).data('target');

                if (target == "_blank") {
                    window.open(url, '_blank').focus();
                } else {
                    window.location.href = url;
                }
            });
        });
    </script>
</head>
<body>
<div class="content-center">
    {include file=$inner_template}
</div>
</body>
</html>



