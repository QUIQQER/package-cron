window.addEvent('quiqqerLoaded', function () {
    require(['Ajax', 'Locale'], function (QUIAjax, QUILocale) {

        const RunningInfo = new Element('div', {
            html: '' +
                '<span style="padding-right: 10px; font-size: 20px">' +
                '   <span class="fa fa-circle-o-notch fa-spin"></span>' +
                '</span>' +
                '<span>' + QUILocale.get('quiqqer/cron', 'message.admin.cron.execution') + '</span>',
            styles: {
                background: '#fff',
                bottom: 20,
                boxShadow: 'rgba(35, 46, 60, .04) 0 2px 4px 0',
                border: '1px solid rgba(101, 109, 119, .16)',
                borderLeft: '.25rem solid #4299e1',
                borderRadius: 4,
                display: 'flex',
                maxWidth: '90%',
                padding: 20,
                position: 'fixed',
                right: 20,
                width: 500,
                zIndex: 10000
            }
        }).inject(document.body);

        QUIAjax.post('package_quiqqer_cron_ajax_execute', function () {
            moofx(RunningInfo).animate({
                bottom: 10,
                opacity: 0
            }, {
                callback: () => {
                    RunningInfo.destroy();
                }
            });
        }, {
            'package': 'quiqqer/cron'
        });
    });
});
