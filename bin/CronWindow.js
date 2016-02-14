/**
 * Cron Window
 *
 * @module package/quiqqer/cron/bin/CronWindow
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/controls/windows/Confirm
 * @require qui/controls/input/Params
 * @require Ajax
 * @require text!package/quiqqer/cron/bin/CronWindow.html
 * @require css!package/quiqqer/cron/bin/CronWindow.css
 */
define('package/quiqqer/cron/bin/CronWindow', [

    'qui/controls/windows/Confirm',
    'qui/controls/input/Params',
    'Ajax',
    'Locale',

    'text!package/quiqqer/cron/bin/CronWindow.html',
    'css!package/quiqqer/cron/bin/CronWindow.css'

], function (QUIConfirm, QUIParams, Ajax, QUILocale, cronWindowTemplate) {
    "use strict";

    var lg = 'quiqqer/cron';

    return new Class({

        Type   : 'package/quiqqer/cron/bin/CronWindow',
        Extends: QUIConfirm,

        options: {
            title    : QUILocale.get('quiqqer/cron', 'cron.window.add.cron.title'),
            icon     : 'fa fa-clock-o',
            maxWidth : 750,
            maxHeight: 500,

            cronId: null, // if you want to edit a cron

            cancel_button : {
                text      : QUILocale.get('quiqqer/system', 'cancel'),
                textimage : 'fa fa-remove'
            },
            ok_button : {
                text      : QUILocale.get('quiqqer/system', 'ok'),
                textimage : 'fa fa-check'
            }
        },

        initialize: function (options) {
            this.parent(options);

            this.$available = [];

            this.$List  = null;
            this.$Min   = null;
            this.$Hour  = null;
            this.$Day   = null;
            this.$Month = null;

            this.$ParamsControl = null;
        },

        /**
         * Open the Window
         *
         * @return {Object} self
         */
        open: function () {
            this.parent();
            this.Loader.show();

            var self    = this,
                Content = this.getContent();

            Content.set('html', cronWindowTemplate);

            // locale
            Content.getElement(
                '.control-cron-add-intervall-title'
            ).set('html', QUILocale.get(lg, 'cron.interval'));

            Content.getElement(
                '[for="control-cron-add-minute"]'
            ).set('html', QUILocale.get(lg, 'cron.min'));

            Content.getElement(
                '[for="control-cron-add-hour"]'
            ).set('html', QUILocale.get(lg, 'cron.hour'));

            Content.getElement(
                '[for="control-cron-add-day"]'
            ).set('html', QUILocale.get(lg, 'cron.day'));

            Content.getElement(
                '[for="control-cron-add-month"]'
            ).set('html', QUILocale.get(lg, 'cron.month'));

            Content.getElement(
                '[for="control-cron-add-dayOfWeek"]'
            ).set('html', QUILocale.get(lg, 'cron.dayOfWeek'));


            Content.getElement(
                '[for="control-cron-add-params"]'
            ).set('html', QUILocale.get(lg, 'cron.params'));


            // data
            this.$List = Content.getElement('.control-cron-add-list');

            this.$Min       = Content.getElement('[name="min"]');
            this.$Hour      = Content.getElement('[name="hour"]');
            this.$Day       = Content.getElement('[name="day"]');
            this.$Month     = Content.getElement('[name="month"]');
            this.$DayOfWeek = Content.getElement('[name="dayOfWeek"]');

            this.$Params = Content.getElement('[name="params"]');

            this.$List.addEvent('change', function () {
                if (!self.$available) {
                    return;
                }

                if (!self.$ParamsControl) {
                    return;
                }

                var i, len, p, plen;
                var val           = self.$List.value,
                    available     = self.$available,

                    allowedParams = [],
                    params        = [];

                for (i = 0, len = available.length; i < len; i++) {
                    if (available[i].title != val) {
                        continue;
                    }

                    params = available[i].params;

                    for (p = 0, plen = params.length; p < plen; p++) {
                        allowedParams.push(params[p].name);
                    }
                }

                self.$ParamsControl.setAttribute('allowedParams', allowedParams);
            });

            Ajax.get('package_quiqqer_cron_ajax_getAvailableCrons', function (result) {

                self.$available = result;

                for (var i = 0, len = result.length; i < len; i++) {
                    new Element('option', {
                        value: result[i].title,
                        html : result[i].title + ' - ' + result[i].description
                    }).inject(self.$List);
                }

                self.$ParamsControl = new QUIParams(self.$Params);

                if (!self.getAttribute('cronId')) {
                    self.Loader.hide();
                    return;
                }

                Ajax.get('package_quiqqer_cron_ajax_cron_get', function (result) {
                    self.$List.value      = result.title;
                    self.$Min.value       = result.min;
                    self.$Hour.value      = result.hour;
                    self.$Day.value       = result.day;
                    self.$Month.value     = result.month;
                    self.$DayOfWeek.value = result.dayOfWeek;
                    self.$Params.value    = result.params;

                    self.$Params.fireEvent('change');
                    self.$List.fireEvent('change');

                    self.Loader.hide();
                }, {
                    'package': 'quiqqer/cron',
                    cronId   : self.getAttribute('cronId')
                });

            }, {
                'package': 'quiqqer/cron'
            });


            return this;
        },

        /**
         * Add the Cron to the list
         *
         * @return {Object} self
         */
        submit: function () {
            var self = this;

            if (!this.$List) {
                return this;
            }

            if (!this.getContent()) {
                return this;
            }


            if (this.getAttribute('cronId')) {
                Ajax.post('package_quiqqer_cron_ajax_edit', function () {
                    self.fireEvent('submit');
                    self.close();
                }, {
                    'package': 'quiqqer/cron',
                    cronId   : this.getAttribute('cronId'),
                    cron     : this.$List.value,
                    min      : this.$Min.value,
                    hour     : this.$Hour.value,
                    day      : this.$Day.value,
                    month    : this.$Month.value,
                    dayOfWeek: this.$DayOfWeek.value,
                    params   : JSON.encode(this.$ParamsControl.getValue())
                });

                return this;
            }

            Ajax.post('package_quiqqer_cron_ajax_add', function () {
                self.fireEvent('submit');
                self.close();
            }, {
                'package': 'quiqqer/cron',
                cron     : this.$List.value,
                min      : this.$Min.value,
                hour     : this.$Hour.value,
                day      : this.$Day.value,
                month    : this.$Month.value,
                dayOfWeek: this.$DayOfWeek.value,
                params   : JSON.encode(this.$ParamsControl.getValue())
            });

            return this;
        }
    });
});
