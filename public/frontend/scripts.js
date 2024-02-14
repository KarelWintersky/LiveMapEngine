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

/**
 * Notify bar helper: success
 *
 * @param messages array
 * @param timeout seconds
 */
function notifySuccess(messages, timeout = 1) {
    let msg = typeof messages == "string" ? [ messages ] : messages;
    $.notifyBar({
        html: msg.join('<br>'),
        delay: timeout * 1000,
        cssClass: 'success'
    });
}

/**
 * Notify bar helper: error
 *
 * @param messages
 * @param timeout
 */
function notifyError(messages, timeout = 600) {
    let msg = typeof messages == "string" ? [ messages ] : messages;
    $.notifyBar({
        html: msg.join('<br>'),
        delay: timeout * 1000,
        cssClass: 'error'
    });
}

/**
 * Notify bar helper: custom class
 *
 * @param messages
 * @param timeout
 * @param custom_class
 */
function notifyCustom(messages, timeout = 10, custom_class = '') {
    let msg = typeof messages == "string" ? [ messages ] : messages;
    $.notifyBar({
        html: msg.join('<br>'),
        delay: timeout * 1000,
        cssClass: custom_class
    });
}

function notifyFlashMessages(messages) {
    console.log(messages);
    $.each(messages, function (key, value) {
        switch (key) {
            case 'success': {
                notifySuccess(value);
                break;
            }
            case 'error': {
                notifyError(value);
                break;
            }
            default: {
                notifyCustom(value)
                break;
            }
        }
    });
}