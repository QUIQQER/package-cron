/**
 * Control for setting up cron timetables in a user-friendly fashion
 *
 * @module package/quiqqer/cron/bin/controls/CronTime
 * @author www.pcsg.de (Patrick Müller)
 *
 * @require qui/controls/Control
 * @require Ajax
 */
define('package/quiqqer/cron/bin/controls/CronTime', [

    'qui/controls/Control',
    'qui/controls/loader/Loader',
    'qui/controls/buttons/Select',

    'Locale',
    'Ajax',

    'css!package/quiqqer/cron/bin/controls/CronTime.css'

], function (QUIControl, QUILoader, QUISelect, QUILocale, QUIAjax) {
    "use strict";

    var lg = 'quiqqer/cron';

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/cron/bin/controls/CronTime',

        Binds: [
            '$loadIntervalOptions',
            '$change',
            '$showCronStyleInput'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$CronTime       = null;
            this.$IntervalSelect = null;
            this.$OptionsElm     = null;
            this.Loader          = new QUILoader();

            this.$SelectMinute    = null;
            this.$SelectHour      = null;
            this.$SelectDay       = null;
            this.$SelectMonth     = null;
            this.$SelectDayOfWeek = null;

            this.$minute    = '*';
            this.$hour      = '*';
            this.$day       = '*';
            this.$month     = '*';
            this.$dayofweek = '*';
        },

        /**
         * create the domnode element
         *
         * @return {HTMLDivElement}
         */
        create: function () {
            var i, len, label;
            var self = this;

            this.$Elm = new Element('div', {
                'class': 'quiqqer-cron-crontime',
                html   : '<div class="quiqqer-cron-crontime-interval">' +
                '</div>' +
                '<div class="quiqqer-cron-crontime-options"></div>'
            });

            //QUILocale.get(lg, 'controls.crontime.label.interval')

            this.$IntervalSelect = new QUISelect({
                'class'  : 'quiqqer-cron-crontime-interval-select',
                showIcons: false,
                events   : {
                    onChange: this.$loadIntervalOptions
                }
            }).inject(
                this.$Elm.getElement(
                    '.quiqqer-cron-crontime-interval'
                )
            );

            this.$OptionsElm = this.$Elm.getElement('.quiqqer-cron-crontime-options');

            var intervals = [
                'everyminute', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'cron'
            ];

            for (i = 0, len = intervals.length; i < len; i++) {
                this.$IntervalSelect.appendChild(
                    QUILocale.get(lg, 'controls.crontime.intervalselect.option.' + intervals[i]),
                    intervals[i],
                    false
                );
            }

            this.$SelectMinute = new QUISelect({
                'class'  : 'quiqqer-cron-crontime-number-select',
                showIcons: false,
                events   : {
                    onChange: function (value) {
                        self.$minute = value;
                        self.$change();
                    }
                }
            });

            for (i = 0; i < 60; i++) {
                label = i;

                if (i < 10) {
                    label = "0" + i;
                }

                this.$SelectMinute.appendChild(
                    label,
                    i,
                    false
                );
            }

            this.$SelectHour = new QUISelect({
                'class'  : 'quiqqer-cron-crontime-number-select',
                showIcons: false,
                events   : {
                    onChange: function (value) {
                        self.$hour = value;
                        self.$change();
                    }
                }
            });

            for (i = 0; i < 24; i++) {
                label = i;

                if (i < 10) {
                    label = "0" + i;
                }

                this.$SelectHour.appendChild(
                    label,
                    i,
                    false
                );
            }

            this.$SelectDay = new QUISelect({
                'class'  : 'quiqqer-cron-crontime-number-select',
                showIcons: false,
                events   : {
                    onChange: function (value) {
                        self.$day = value;
                        self.$change();
                    }
                }
            });

            for (i = 0; i < 31; i++) {
                this.$SelectDay.appendChild(
                    i + 1 + ".",
                    i + 1,
                    false
                );
            }

            this.$SelectMonth = new QUISelect({
                'class'  : 'quiqqer-cron-crontime-month-select',
                showIcons: false,
                events   : {
                    onChange: function (value) {
                        self.$month = value;
                        self.$change();
                    }
                }
            });

            for (i = 0; i < 12; i++) {
                this.$SelectMonth.appendChild(
                    QUILocale.get(lg, 'controls.crontime.month.' + (i + 1)),
                    i + 1,
                    false
                );
            }

            this.$SelectDayOfWeek = new QUISelect({
                'class'  : 'quiqqer-cron-crontime-dayofweek-select',
                showIcons: false,
                events   : {
                    onChange: function (value) {
                        self.$dayofweek = value;
                        self.$change();
                    }
                }
            });

            var daysOfWeek = [
                'su', 'mo', 'tu', 'we', 'th', 'fr', 'sa'
            ];

            for (i = 0, len = daysOfWeek.length; i < len; i++) {
                this.$SelectDayOfWeek.appendChild(
                    QUILocale.get(lg, 'controls.crontime.dayofweek.' + daysOfWeek[i]),
                    i,
                    false
                );
            }

            return this.$Elm;
        },

        /**
         * Load option inputs depending on interval
         *
         * @param interval
         */
        $loadIntervalOptions: function (interval) {
            var self = this;

            this.$OptionsElm.set('html', '');

            this.$minute    = '*';
            this.$hour      = '*';
            this.$day       = '*';
            this.$month     = '*';
            this.$dayofweek = '*';

            switch (interval) {
                case 'everyminute':
                    this.$change();
                    break;

                case 'hourly':
                    this.$OptionsElm.set(
                        'html',
                        '<span>' + QUILocale.get(lg, 'controls.crontime.label.minute') + '</span>' +
                        '<div class="quiqqer-cron-crontime-hour"></div>'
                    );

                    this.$SelectMinute.inject(
                        this.$OptionsElm.getElement(
                            '.quiqqer-cron-crontime-hour'
                        )
                    );
                    this.$SelectMinute.setValue(0);

                    break;

                case 'daily':
                    this.$OptionsElm.set(
                        'html',
                        '<span>' + QUILocale.get(lg, 'controls.crontime.label.at') + '</span>' +
                        '<div class="quiqqer-cron-crontime-hour"></div>' +
                        '<span>:</span>' +
                        '<div class="quiqqer-cron-crontime-minute"></div>'
                    );

                    this.$SelectHour.inject(
                        this.$OptionsElm.getElement(
                            '.quiqqer-cron-crontime-hour'
                        )
                    );
                    this.$SelectHour.setValue(0);

                    this.$SelectMinute.inject(
                        this.$OptionsElm.getElement(
                            '.quiqqer-cron-crontime-minute'
                        )
                    );
                    this.$SelectMinute.setValue(0);

                    break;

                case 'weekly':
                    this.$OptionsElm.set(
                        'html',
                        '<span>' + QUILocale.get(lg, 'controls.crontime.label.on') + '</span>' +
                        '<div class="quiqqer-cron-crontime-dayofweek"></div>' +
                        '<span>' + QUILocale.get(lg, 'controls.crontime.label.at') + '</span>' +
                        '<div class="quiqqer-cron-crontime-hour"></div>' +
                        '<span>:</span>' +
                        '<div class="quiqqer-cron-crontime-minute"></div>'
                    );

                    this.$SelectDayOfWeek.inject(
                        this.$OptionsElm.getElement(
                            '.quiqqer-cron-crontime-dayofweek'
                        )
                    );
                    this.$SelectDayOfWeek.setValue(0);

                    this.$SelectHour.inject(
                        this.$OptionsElm.getElement(
                            '.quiqqer-cron-crontime-hour'
                        )
                    );
                    this.$SelectHour.setValue(0);

                    this.$SelectMinute.inject(
                        this.$OptionsElm.getElement(
                            '.quiqqer-cron-crontime-minute'
                        )
                    );
                    this.$SelectMinute.setValue(0);

                    break;

                case 'monthly':
                    this.$OptionsElm.set(
                        'html',
                        '<span>' + QUILocale.get(lg, 'controls.crontime.label.on') + '</span>' +
                        '<div class="quiqqer-cron-crontime-day"></div>' +
                        '<span>' + QUILocale.get(lg, 'controls.crontime.label.at') + '</span>' +
                        '<div class="quiqqer-cron-crontime-hour"></div>' +
                        '<span>:</span>' +
                        '<div class="quiqqer-cron-crontime-minute"></div>'
                    );

                    this.$SelectDay.inject(
                        this.$OptionsElm.getElement(
                            '.quiqqer-cron-crontime-day'
                        )
                    );
                    this.$SelectDay.setValue(1);

                    this.$SelectHour.inject(
                        this.$OptionsElm.getElement(
                            '.quiqqer-cron-crontime-hour'
                        )
                    );
                    this.$SelectHour.setValue(0);

                    this.$SelectMinute.inject(
                        this.$OptionsElm.getElement(
                            '.quiqqer-cron-crontime-minute'
                        )
                    );
                    this.$SelectMinute.setValue(0);

                    break;

                case 'yearly':
                    this.$OptionsElm.set(
                        'html',
                        '<span>' + QUILocale.get(lg, 'controls.crontime.label.on') + '</span>' +
                        '<div class="quiqqer-cron-crontime-day"></div>' +
                        '<div class="quiqqer-cron-crontime-month"></div>' +
                        '<span>' + QUILocale.get(lg, 'controls.crontime.label.at') + '</span>' +
                        '<div class="quiqqer-cron-crontime-hour"></div>' +
                        '<span>:</span>' +
                        '<div class="quiqqer-cron-crontime-minute"></div>'
                    );

                    this.$SelectDay.inject(
                        this.$OptionsElm.getElement(
                            '.quiqqer-cron-crontime-day'
                        )
                    );
                    this.$SelectDay.setValue(1);

                    this.$SelectMonth.inject(
                        this.$OptionsElm.getElement(
                            '.quiqqer-cron-crontime-month'
                        )
                    );
                    this.$SelectMonth.setValue(1);

                    this.$SelectHour.inject(
                        this.$OptionsElm.getElement(
                            '.quiqqer-cron-crontime-hour'
                        )
                    );
                    this.$SelectHour.setValue(0);

                    this.$SelectMinute.inject(
                        this.$OptionsElm.getElement(
                            '.quiqqer-cron-crontime-minute'
                        )
                    );
                    this.$SelectMinute.setValue(0);
                    break;

                case 'cron':
                    var inputs = [
                        'minute', 'hour', 'day', 'month', 'dayofweek'
                    ];

                    for (var i = 0, len = inputs.length; i < len; i++) {
                        var Label = new Element('label', {
                            'class': 'quiqqer-cron-crontime-cron-label',
                            html   : '<span>' +
                            QUILocale.get(lg, 'controls.crontime.label.' + inputs[i]) +
                            '</span>'
                        }).inject(this.$OptionsElm);

                        new Element('input', {
                            'class'    : 'quiqqer-cron-crontime-cron-input',
                            'data-type': inputs[i],
                            type       : 'text',
                            events     : {
                                change: function (event) {
                                    var Input = event.target;

                                    Input.value = Input.value.replace(/[^\d\*\/\-]/gi, '');

                                    switch (Input.getProperty('data-type')) {
                                        case 'minute':
                                            self.$minute = Input.value;
                                            break;

                                        case 'hour':
                                            self.$hour = Input.value;
                                            break;

                                        case 'day':
                                            self.$day = Input.value;
                                            break;

                                        case 'month':
                                            self.$month = Input.value;
                                            break;

                                        case 'dayofweek':
                                            self.$dayofweek = Input.value;
                                            break;
                                    }

                                    self.$change();
                                }
                            }
                        }).inject(Label, 'top');
                    }

                    this.$Elm.getElement(
                        '.quiqqer-cron-crontime-cron-input'
                    ).focus();

                    break;
            }
        },

        /**
         * Fires change event with current value
         */
        $change: function () {
            this.fireEvent(
                'change',
                [this.$minute, this.$hour, this.$day, this.$month, this.$dayofweek, this]
            );
        },

        /**
         * Set value to control
         *
         * @param minute
         * @param hour
         * @param day
         * @param month
         * @param dayofweek
         */
        setValue: function (minute, hour, day, month, dayofweek) {
            this.$minute    = minute;
            this.$hour      = hour;
            this.$day       = day;
            this.$month     = month;
            this.$dayofweek = dayofweek;

            if (minute.match(/[^\d\*]/gi) ||
                hour.match(/[^\d\*]/gi) ||
                day.match(/[^\d\*]/gi) ||
                month.match(/[^\d\*]/gi) ||
                dayofweek.match(/[^\d\*]/gi)
            ) {
                this.$showCronStyleInput();
                return;
            }

            if (minute == '*' &&
                hour == '*' &&
                day == '*' &&
                month == '*' &&
                dayofweek == '*') {
                this.$IntervalSelect.setValue('everyminute');
                return;
            }

            if (minute != '*' &&
                hour == '*' &&
                day == '*' &&
                month == '*' &&
                dayofweek == '*') {
                this.$IntervalSelect.setValue('hourly');
                this.$SelectMinute.setValue(minute);
                return;
            }

            if (minute != '*' &&
                hour != '*' &&
                day == '*' &&
                month == '*' &&
                dayofweek == '*') {
                this.$IntervalSelect.setValue('daily');
                this.$SelectMinute.setValue(minute);
                this.$SelectHour.setValue(hour);
                return;
            }

            if (minute != '*' &&
                hour != '*' &&
                day == '*' &&
                month == '*' &&
                dayofweek != '*') {
                this.$IntervalSelect.setValue('weekly');
                this.$SelectMinute.setValue(minute);
                this.$SelectHour.setValue(hour);
                this.$SelectDayOfWeek.setValue(dayofweek);
                return;
            }

            if (minute != '*' &&
                hour != '*' &&
                day != '*' &&
                month == '*' &&
                dayofweek == '*') {
                this.$IntervalSelect.setValue('monthly');
                this.$SelectMinute.setValue(minute);
                this.$SelectHour.setValue(hour);
                this.$SelectDay.setValue(day);
                return;
            }

            if (minute != '*' &&
                hour != '*' &&
                day != '*' &&
                month != '*' &&
                dayofweek == '*') {
                this.$IntervalSelect.setValue('yearly');
                this.$SelectMinute.setValue(minute);
                this.$SelectHour.setValue(hour);
                this.$SelectDay.setValue(day);
                this.$SelectMonth.setValue(month);
                return;
            }

            this.$showCronStyleInput();
        },

        /**
         * Show cron style type input
         */
        $showCronStyleInput: function () {
            var m  = this.$minute,
                h  = this.$hour,
                d  = this.$day,
                mo = this.$month,
                dw = this.$dayofweek;

            this.$IntervalSelect.setValue('cron');

            var cronStyleInputs = this.$Elm.getElements(
                '.quiqqer-cron-crontime-cron-input'
            );


            for (var i = 0, len = cronStyleInputs.length; i < len; i++) {
                var Elm = cronStyleInputs[i];

                switch (Elm.getProperty('data-type')) {
                    case 'minute':
                        Elm.value = m;
                        break;

                    case 'hour':
                        Elm.value = h;
                        break;

                    case 'day':
                        Elm.value = d;
                        break;

                    case 'month':
                        Elm.value = mo;
                        break;

                    case 'dayofweek':
                        Elm.value = dw;
                        break;
                }
            }

            this.$minute    = m;
            this.$hour      = h;
            this.$day       = d;
            this.$month     = mo;
            this.$dayofweek = dw;
        },

        /**
         * Get current cron time values
         *
         * @return {Object}
         */
        getValue: function () {
            return {
                minute   : this.$minute,
                hour     : this.$hour,
                day      : this.$day,
                month    : this.$month,
                dayOfWeek: this.$dayofweek
            };
        }
    });
});
