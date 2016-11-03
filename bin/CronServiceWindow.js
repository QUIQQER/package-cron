/**
 *
 */
define('package/quiqqer/cron/bin/CronServiceWindow', [

    'qui/QUI',
    'qui/controls/windows/Popup',
    'qui/controls/buttons/Button',
    'Mustache',
    'Locale',
    'Ajax',
    'qui/controls/desktop/panels/Sheet',

    'text!package/quiqqer/cron/bin/CronServiceWindow.html',
    'text!package/quiqqer/cron/bin/CronServiceWindowRegistration.html',
    'css!package/quiqqer/cron/bin/CronServiceWindow.css'

], function (QUI, QUIPopup, QUIButton, Mustache, QUILocale, QUIAjax, QUISheets, template, registrationTemplate) {
    "use strict";

    var lg = 'quiqqer/cron';

    return new Class({

        Extends: QUIPopup,
        Type   : 'package/quiqqer/cron/bin/CronServiceWindow',

        Binds: [
            '$onSubmit',
            '$onOpen',
            'showRegistration'
        ],

        options: {
            title    : QUILocale.get(lg, 'cron.window.cronservice.title'),
            icon     : 'fa fa-cloud',
            maxWidth : 400,
            maxHeight: 600,
            autoclose: false,
            buttons  : false
        },

        initialize: function (options) {
            this.parent(options);

            this.registered = false;

            this.addEvents({
                onOpen: this.$onOpen
            });
        },

        $onOpen: function () {
            var Content = this.getContent();

            Content.set('html', '');
            Content.addClass('quiqqer-cron-cronserviceWindow');

            this.refresh();
        },

        /**
         * refresh
         */
        refresh: function () {
            var self    = this,
                Content = this.getContent();

            this.Loader.show();

            QUIAjax.get('package_quiqqer_cron_ajax_cronservice_getStatus', function (result) {
                console.log(result);
                var status = result;

                var statusText = QUILocale.get(lg, 'cron.window.cronservice.status.text.unregistered');

                if (status['status'] == 1) {
                    statusText = QUILocale.get(lg, 'cron.window.cronservice.status.text.registered');
                }
                if (status['status'] == 2) {
                    statusText = QUILocale.get(lg, 'cron.window.cronservice.status.text.inactive');
                }

                Content.set('html', Mustache.render(template, {
                    cron_window_cronservice_content_title                           : QUILocale.get(lg, 'cron.window.cronservice.content.title'),
                    cron_window_cronservice_content_about_title                     : QUILocale.get(lg, 'cron.window.cronservice.content.about.title'),
                    cron_window_cronservice_content_about_text                      : QUILocale.get(lg, 'cron.window.cronservice.content.about.text'),
                    cron_window_cronservice_content_status_title                    : QUILocale.get(lg, 'cron.window.cronservice.content.status.title'),
                    cron_window_cronservice_content_status_text                     : QUILocale.get(lg, 'cron.window.cronservice.content.status.text'),
                    cron_window_cronservice_content_btn_unregister                  : QUILocale.get(lg, 'cron.window.cronservice.content.register.btn.unregister'),
                    cron_window_cronservice_content_btn_register                    : QUILocale.get(lg, 'cron.window.cronservice.content.btn.register'),
                    cron_window_cronservice_content_register_lbl_stats_status       : QUILocale.get(lg, 'cron.window.cronservice.content.register.lbl.stats.status'),
                    cron_window_cronservice_content_register_lbl_stats_errors       : QUILocale.get(lg, 'cron.window.cronservice.content.register.lbl.stats.errors'),
                    cron_window_cronservice_content_register_lbl_stats_lastExecution: QUILocale.get(lg, 'cron.window.cronservice.content.register.lbl.stats.lastExecution'),
                    statusText                                                      : statusText,
                    status                                                          : status['status'],
                    statusErrors                                                    : status['errors'],
                    statusLastExecution                                             : status['last_execution'],
                    registered                                                      : (status['status'] != 0),
                    active                                                          : (status['status'] == 1),
                    inactive                                                        : (status['status'] == 2)
                }));

                self.registered = (status['status'] != 0);

                var Buttons = Content.getElement('.quiqqer-cron-cronservicewindow-buttons');

                // get the button text : register or unregister
                var btnText = QUILocale.get(lg, 'cron.window.cronservice.content.btn.register');
                if (self.registered) {
                    btnText = QUILocale.get(lg, 'cron.window.cronservice.content.btn.unregister');
                }

                new QUIButton({
                    text     : btnText,
                    textimage: 'fa fa-arrow-right',
                    events   : {
                        onClick: function (Button) {
                            if (!self.registered) {
                                self.showRegistration();
                                return;
                            }
                            Button.setAttribute('text', QUILocale.get('quiqqer/cron', 'cron.window.cronservice.content.btn.unregister.confirm'));
                            if (Button.getAttribute('clickcnt') == 1) {
                                self.unregister().then(function () {
                                    self.refresh();
                                });
                            }
                            Button.setAttribute('clickcnt', 1);
                        }
                    },
                    styles   : {
                        'float': 'none',
                        margin : '0 auto',
                        width  : 200
                    }
                }).inject(Buttons);


                self.Loader.hide();
            }, {
                'package': lg,
                'onError': function () {
                    self.Loader.hide();
                }
            });
        },

        /**
         * Opens the registration sheet
         */
        showRegistration: function () {
            var self = this;

            new QUISheets({
                header : true,
                icon   : 'fa fa-cloud',
                title  : QUILocale.get(lg, 'cron.window.cronservice.title'),
                buttons: false,
                events : {
                    onOpen: function (Sheet) {
                        var Content = Sheet.getContent();

                        Content.set('html', Mustache.render(registrationTemplate, {
                            cron_window_cronservice_registration_title                : QUILocale.get(lg, 'cron.window.cronservice.registration.title'),
                            cron_window_cronservice_content_register_txt_email_title  : QUILocale.get(lg, 'cron.window.cronservice.content.register.txt.email.title'),
                            cron_window_cronservice_content_register_placeholder_email: QUILocale.get(lg, 'cron.window.cronservice.content.register.placeholder.email'),
                            cron_window_cronservice_content_btn_register              : QUILocale.get(lg, 'cron.window.cronservice.registration.title')
                        }));

                        var Email = Content.getElement('.quiqqer-cron-cronservicewindow-registration-txt-email');

                        Content.getElement('.quiqqer-cron-cronservicewindow-btn-register').addEvent('click', function () {
                            self.Loader.show();
                            self.register(Email.value).then(function () {
                                self.refresh();
                                Sheet.destroy();
                            });
                        });
                    },

                    onClose: function (Sheet) {
                        Sheet.destroy();
                    }
                }
            }).inject(this.$Elm).show();
        },

        /**
         * Register a email to the cron service
         *
         * @param {String} email
         * @returns {Promise}
         */
        register: function (email) {
            return new Promise(function (resolve, reject) {
                QUIAjax.post('package_quiqqer_cron_ajax_cronservice_sendRegistration', resolve, {
                    'package': lg,
                    'email'  : email,
                    onError  : reject
                });
            });
        },

        /**
         * Unregister a email to the cron service
         *
         * @returns {Promise}
         */
        unregister: function () {
            return new Promise(function (resolve, reject) {
                QUIAjax.get('package_quiqqer_cron_ajax_cronservice_revokeRegistration', resolve, {
                    'package': lg,
                    onError  : reject
                });
            });
        }
    });
});
