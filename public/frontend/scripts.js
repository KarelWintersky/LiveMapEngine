Number.prototype.between = function(a, b) {
    let min = Math.min.apply(Math, [a, b]),
        max = Math.max.apply(Math, [a, b]);
    return this > min && this < max;
};

Number.prototype.inbound = function(a, b) {
    let min = Math.min.apply(Math, [a, b]),
        max = Math.max.apply(Math, [a, b]);
    return this >= min && this <= max;
};

$.fn.escape = function (callback) {
    return this.each(function () {
        $(document).on("keydown", this, function (e) {
            let keycode = ((typeof e.keyCode != 'undefined' && e.keyCode) ? e.keyCode : e.which);
            if (keycode === 27) {
                callback.call(this, e);
            }
        });
    });
};

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

if (false) {
    // id="bind-actor-click-inside-colorbox"
// обрабатываем клик по ссылке внутри попап окна
// (на самом деле надо проверять, это ссылка на ту же карту или нет?)
//@todo: протестировать, отладить!
    $(document).on('click', '#cboxLoadedContent a', function(){ // здесь другой элемент ловит событие!
        let href = $(this).attr('href');
        let wlh = window.location.href;

        if (href.indexOf( '#view' ) == 0) { // если href содержит ссылку на блок с информацией...
            let href_params = href.match(/view=\[(.*)\]/);
            if (href_params != null) {
                history.pushState('', document.title, window.location.pathname + href);
                // toggleContentViewBox(href_params[1], '');
            }
        } else {
            window.location.assign(href);
            window.location.reload(true);
        }

        return false;
    });
}

