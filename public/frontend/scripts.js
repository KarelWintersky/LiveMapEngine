$(document).ready(function() {
    // notifyFlashMessages(flash_messages);

    // Action redirect
    $(document).on('click', "*[data-action='redirect']", function (event) {
        event.preventDefault();
        let url = $(this).data('url');
        let target = $(this).data('target') || '';
        let confirm_message = $(this).data('confirm-message') || '';

        console.log("Redirect: ", url, target, confirm_message);

        if (confirm_message.length > 0) {
            if (!confirm(confirm_message)) {
                return false;
            }
        }

        if (target == "_blank") {
            window.open(url, '_blank').focus();
        } else {
            window.location.assign(url);
        }
    }).on('click', '.action-close', function (){
        window.close();
    });

    /*
    // клик в любое место ячейки таблицы вызывает смену чекбокса
    $("td:has(label:has(input[type='checkbox']))").on('click', function (e){
        let checkbox = $(this).find('input:checkbox');
        checkbox.prop('checked', !checkbox.prop('checked'));
        e.preventDefault();
    });
    */

});