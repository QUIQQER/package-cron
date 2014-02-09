

define('package/quiqqer/cron/bin/AddCronWindow', [

    'qui/controls/windows/Confirm',
    'Ajax',

    'css!package/quiqqer/cron/bin/AddCronWindow'

], function(QUIConfirm, Ajax)
{
    "use strict";


    return new Class({

        Type : 'package/quiqqer/cron/bin/AddCronWindow',
        Extends : QUIConfirm,

        options : {
            title : 'Cron hinzufügen',
            icon : 'icon-time',
            maxWidth : 500,
            maxHeight : 300
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
                            'Intervall:' +
                        '</div>' +

                        '<div class="control-cron-add-intervall-entries">' +
                            '<div class="control-cron-add-intervall-entry">' +
                                '<input type="text" name="min" id="control-cron-add-minute" />' +
                                '<label for="control-cron-add-minute">Minute</label>' +
                            '</div>' +
                            '<div class="control-cron-add-intervall-entry">' +
                                '<input type="text" name="hour" id="control-cron-add-hour" />' +
                                '<label for="control-cron-add-hour">Stunde</label>' +
                            '</div>' +
                            '<div class="control-cron-add-intervall-entry">' +
                                '<input type="text" name="day" id="control-cron-add-day" />' +
                                '<label for="control-cron-add-day">Tag</label>' +
                            '</div>' +
                            '<div class="control-cron-add-intervall-entry">' +
                                '<input type="text" name="month" id="control-cron-add-month" />' +
                                '<label for="control-cron-add-month">Monat</label>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +

                '</div>'
            );

            this.$List = Content.getElement( '.control-cron-add-list' );

            this.$Min   = Content.getElement( '[name="min"]' );
            this.$Hour  = Content.getElement( '[name="hour"]' );
            this.$Day   = Content.getElement( '[name="day"]' );
            this.$Month = Content.getElement( '[name="month"]' );


            Ajax.get('package_quiqqer_cron_ajax_getAvailableCrons', function(result)
            {
                var i, len;

                self.$available = result;

                for ( i = 0, len = result.length; i < len; i++ )
                {
                    new Element('option', {
                        value : result[ i ].title,
                        html  : result[ i ].description
                    }).inject( self.$List );
                }

                self.Loader.hide();

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
            if ( !this.$List ) {
                return this;
            }

            if ( !this.getContent() ) {
                return this;
            }

            Ajax.post('package_quiqqer_cron_ajax_add', function(result)
            {

                console.log( result );

            }, {
                'package' : 'quiqqer/cron',
                cron  : this.$List.value,
                min   : this.$Min.value,
                hour  : this.$Hour.value,
                day   : this.$Day.value,
                month : this.$Month.value
            });
        }

    });

});