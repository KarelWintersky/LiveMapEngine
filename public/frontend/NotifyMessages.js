class NotifyMessages {

    static display(messages) {
        console.log(messages);
        $.each(messages, function (key, value) {
            switch (key) {
                case 'success': {
                    NotifyMessages.success(value);
                    break;
                }
                case 'error': {
                    NotifyMessages.error(value);
                    break;
                }
                default: {
                    NotifyMessages.custom(value)
                    break;
                }
            }
        });
    }

    /**
     * Notify bar helper: success
     *
     * @param messages array
     * @param timeout seconds
     */
    static success(messages, timeout = 1) {
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
    static error(messages, timeout = 600) {
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
    static custom(messages, timeout = 10, custom_class = '') {
        let msg = typeof messages == "string" ? [ messages ] : messages;
        $.notifyBar({
            html: msg.join('<br>'),
            delay: timeout * 1000,
            cssClass: custom_class
        });
    }



}