function adminInit() {
    require(['qui/controls/windows/Confirm', 'Locale'], function (QUIConfirm, QUILocale) {
        const lg = 'quiqqer/cron';

        const waitForLocale = setInterval(() => {
            if (!QUILocale.exists(lg, 'message.cron.admin.info.24h')) {
                return;
            }

            new QUIConfirm({
                maxHeight: 400,
                maxWidth: 600,
                autoclose: true,
                backgroundClosable: false,
                titleCloseButton: false,
                information: QUILocale.get(lg, 'message.cron.admin.info.24h'),
                title: QUILocale.get(lg, 'message.cron.admin.info.24h.title'),
                texticon: 'fa fa-exclamation-triangle',
                text: QUILocale.get(lg, 'message.cron.admin.info.24h.text'),
                icon: 'fa fa-exclamation-triangle',
                cancel_button: false,
                ok_button: {
                    text: QUILocale.get(lg, 'message.cron.admin.info.24h.btn.submit'),
                    textimage: 'icon-ok fa fa-check'
                }
            }).open();

            clearInterval(waitForLocale);
        }, 500);
    });
}

if (typeof window.whenQuiLoaded !== 'undefined') {
    window.whenQuiLoaded.then(adminInit);
} else {
    document.addEvent('domready', adminInit);
}