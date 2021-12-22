/**
 * Update check
 *
 * @module package/quiqqer/cron/bin/UpdateInfo
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/cron/bin/UpdateInfo', [

    'qui/QUI',
    'Ajax',
    'Locale',
    URL_OPT_DIR + 'bin/quiqqer-asset/animejs/animejs/lib/anime.min.js',

    'css!package/quiqqer/cron/bin/UpdateInfo.css'

], function (QUI, QUIAjax, QUILocale, anime) {
    "use strict";

    QUIAjax.post("package_quiqqer_cron_ajax_updateCheck", function (result) {
        if (!result.length) {
            return;
        }

        const Message = new Element("div", {
            class : "updates-are-available",
            role  : 'alert',
            html  : '<div class="updates-are-available-text">' +
                    '    <div class="updates-are-available-icon">' +
                    '        <span class="fa fa-exclamation"></span>' +
                    '    </div>' +
                    '    <div>' +
                    '        ' + QUILocale.get('quiqqer/cron', 'message.updates.available') +
                    '    </div>' +
                    '</div>',
            styles: {
                opacity: 0
            }
        }).inject(document.body);

        new Element('button', {
            html  : QUILocale.get('quiqqer/cron', 'message.updates.available.button'),
            events: {
                click: function () {
                    Message.destroy();

                    require([
                        'utils/Panels',
                        'controls/packages/Panel'
                    ], function (PanelUtils, SystemPanel) {
                        PanelUtils.openPanelInTasks(
                            new SystemPanel()
                        );
                    });
                }
            }
        }).inject(Message);


        anime({
            targets : Message,
            duration: 250,
            opacity : 1,
            top     : 30,
            easing  : 'easeOutSine',
            complete: function () {
                anime({
                    delay   : 4000,
                    targets : Message,
                    duration: 250,
                    opacity : 0,
                    top     : 0,
                    easing  : 'easeOutSine',
                    complete: function () {
                        Message.destroy();
                    }
                });
            }
        });

    }, {
        "package": "quiqqer/cron"
    });
});