
/**
 * Cron Window
 *
 * @module URL_OPT_DIR/quiqqer/cron/bin/CronWindow
 * @author www.namerobot.com (Henning Leutz)
 *
 * @require qui/controls/windows/Confirm
 * @require qui/controls/input/Params
 * @require Ajax
 * @require css!package/quiqqer/cron/bin/CronWindow.css
 */

define('package/quiqqer/cron/bin/CronWindow', [

    'qui/controls/windows/Confirm',
    'qui/controls/input/Params',
    'Ajax',
    'Locale',

    'css!package/quiqqer/cron/bin/CronWindow.css'

], function(QUIConfirm, QUIParams, Ajax, QUILocale)
{
    "use strict";


    return new Class({

        Type : 'package/quiqqer/cron/bin/CronWindow',
        Extends : QUIConfirm,

        options : {
            title     : QUILocale.get( 'quiqqer/cron', 'cron.window.add.cron.title' ),
            icon      : 'icon-time',
            maxWidth  : 440,
            maxHeight : 500,

            cronId : null // if you want to edit a cron
        },

        initialize : function(options)
        {
            this.parent( options );

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
         * @return {self}
         */
        open : function()
        {
            this.parent();
            this.Loader.show();

            var self    = this,
                Content = this.getContent();

            Content.set(
                'html',

                '<div class="control-cron-add">' +
                    '<label for="control-cron-add-list">' +
                        'Cron' +
                    '</label>' +
                    '<select ' +
                        'class="control-cron-add-list" ' +
                        'id="control-cron-add-list">' +
                    '</select>' +

                    '<div class="control-cron-add-intervall">' +
                        '<div class="control-cron-add-intervall-title">' +
                            QUILocale.get( 'quiqqer/cron', 'cron.interval' ) +
                        '</div>' +

                        '<div class="control-cron-add-intervall-entries">' +
                            '<div class="control-cron-add-intervall-entry">' +
                                '<input type="text" name="min" id="control-cron-add-minute" />' +
                                '<label for="control-cron-add-minute">'+
                                    QUILocale.get( 'quiqqer/system', 'minute' ) +
                                '</label>' +
                            '</div>' +
                            '<div class="control-cron-add-intervall-entry">' +
                                '<input type="text" name="hour" id="control-cron-add-hour" />' +
                                '<label for="control-cron-add-hour">'+
                                    QUILocale.get( 'quiqqer/system', 'hour' ) +
                                '</label>' +
                            '</div>' +
                            '<div class="control-cron-add-intervall-entry">' +
                                '<input type="text" name="day" id="control-cron-add-day" />' +
                                '<label for="control-cron-add-day">'+
                                    QUILocale.get( 'quiqqer/cron', 'cron.day' ) +
                                '</label>' +
                            '</div>' +
                            '<div class="control-cron-add-intervall-entry">' +
                                '<input type="text" name="month" id="control-cron-add-month" />' +
                                '<label for="control-cron-add-month">'+
                                    QUILocale.get( 'quiqqer/system', 'month' ) +
                                '</label>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +

                    '<div class="control-cron-add-params-container">' +
                        '<label for="control-cron-add-params">' +
                            QUILocale.get( 'quiqqer/cron', 'cron.params' ) +
                        '</label>' +
                        '<input type="text" name="params" id="control-cron-add-params"  />' +
                    '</div>' +

                '</div>'
            );

            this.$List = Content.getElement( '.control-cron-add-list' );

            this.$Min   = Content.getElement( '[name="min"]' );
            this.$Hour  = Content.getElement( '[name="hour"]' );
            this.$Day   = Content.getElement( '[name="day"]' );
            this.$Month = Content.getElement( '[name="month"]' );

            this.$Params = Content.getElement( '[name="params"]' );


            Ajax.get('package_quiqqer_cron_ajax_getAvailableCrons', function(result)
            {
                var i, len;

                var size = self.getElm().getSize();

                self.$available = result;

                for ( i = 0, len = result.length; i < len; i++ )
                {
                    new Element('option', {
                        value : result[ i ].title,
                        html  : result[ i ].title +' - '+ result[ i ].description
                    }).inject( self.$List );
                }

                self.$ParamsControl = new QUIParams( self.$Params, {
                    windowMaxHeight : size.y,
                    windowMaxWidth  : size.x
                } );

                if ( !self.getAttribute( 'cronId' ) )
                {
                    self.Loader.hide();
                    return;
                }

                Ajax.get('package_quiqqer_cron_ajax_cron_get', function(result)
                {
                    self.$List.value = result.title;

                    self.$Min.value    = result.min;
                    self.$Hour.value   = result.hour;
                    self.$Day.value    = result.day;
                    self.$Month.value  = result.month;
                    self.$Params.value = result.params;

                    self.$Params.fireEvent( 'change' );

                    self.Loader.hide();
                }, {
                    'package' : 'quiqqer/cron',
                    cronId    : self.getAttribute( 'cronId' )
                });

            }, {
                'package' : 'quiqqer/cron'
            });


            return this;
        },

        /**
         * Add the Cron to the list
         *
         * @return {self}
         */
        submit : function()
        {
            var self = this;

            if ( !this.$List ) {
                return this;
            }

            if ( !this.getContent() ) {
                return this;
            }


            if ( this.getAttribute( 'cronId' ) )
            {
                Ajax.post('package_quiqqer_cron_ajax_edit', function()
                {
                    self.fireEvent( 'submit' );
                    self.close();
                }, {
                    'package' : 'quiqqer/cron',
                    cronId : this.getAttribute( 'cronId' ),
                    cron   : this.$List.value,
                    min    : this.$Min.value,
                    hour   : this.$Hour.value,
                    day    : this.$Day.value,
                    month  : this.$Month.value,
                    params : JSON.encode( this.$ParamsControl.getValue() )
                });

                return this;
            }

            Ajax.post('package_quiqqer_cron_ajax_add', function()
            {
                self.fireEvent( 'submit' );
                self.close();
            }, {
                'package' : 'quiqqer/cron',
                cron   : this.$List.value,
                min    : this.$Min.value,
                hour   : this.$Hour.value,
                day    : this.$Day.value,
                month  : this.$Month.value,
                params : JSON.encode( this.$ParamsControl.getValue() )
            });

            return this;
        }
    });
});
