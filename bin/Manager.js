
/**
 * Cron Manager
 *
 * @author www.pcsg.de (Henning Leutz)
 */

define('package/quiqqer/cron/bin/Manager', [

    'qui/controls/desktop/Panel',
    'qui/controls/buttons/Button',
    'controls/grid/Grid',
    'Ajax'

],function(QUIPanel, QUIButton, Grid, Ajax)
{
    "use strict";

    return new Class({

        Extends : QUIPanel,
        Type    : 'package/cron/bin/Manager',

        Binds : [
            '$onCreate'
        ],

        options : {
            title : 'Cron-Manager',
            icon : 'icon-time'
        },

        initialize : function(options)
        {
            this.parent( options );

            this.addEvents({
                onCreate : this.$onCreate,
                onResize : this.$onResize
            });
        },

        /**
         * Load the crons into the grid
         *
         * @return {self}
         */
        loadCrons : function()
        {
            var self = this;

            Ajax.get('package_quiqqer_cron_ajax_getList', function(result)
            {
                console.log( result );

            }, {
                'package' : 'quiqqer/cron'
            });

            return this;
        },

        /**
         * event : on Create
         */
        $onCreate : function()
        {
            this.addButton(
                new QUIButton({
                    text : 'Cron hinzufügen',
                    textimage : 'icon-plus',
                    events :
                    {
                        onClick : function()
                        {
                            require([
                                'package/quiqqer/cron/bin/AddCronWindow'
                            ], function(Window)
                            {
                                new Window({

                                }).open();
                            });
                        }
                    }
                })
            );

            var Content   = this.getContent(),
                size      = Content.getSize(),

                Container = new Element('div', {
                    'class' : 'box',
                    styles : {
                        width : '100%',
                        height : '100%'
                    }
                }).inject( Content );


            this.$Grid = new Grid(Container, {
                columnModel : [{
                    header    : 'Status',
                    dataIndex : 'activebtn',
                    dataType  : 'button',
                    width     : 50
                }, {
                    header    : 'Cron-Name',
                    dataIndex : 'name',
                    dataType  : 'string',
                    width     : 150
                }, {
                    header    : 'Min',
                    dataIndex : 'min',
                    dataType  : 'string',
                    width     : 50
                }, {
                    header    : 'Std',
                    dataIndex : 'hour',
                    dataType  : 'string',
                    width     : 50
                }, {
                    header    : 'Tag',
                    dataIndex : 'day',
                    dataType  : 'string',
                    width     : 50
                }, {
                    header    : 'Monat',
                    dataIndex : 'month',
                    dataType  : 'string',
                    width     : 50
                }, {
                    header    : 'Cron-Beschreibung',
                    dataIndex : 'desc',
                    dataType  : 'string',
                    width     : 150
                }, {
                    header    : 'Parameter',
                    dataIndex : 'params',
                    dataType  : 'string',
                    width     : 150
                }]
            });

            this.loadCrons();
        },

        /**
         * event : on resize
         */
        $onResize : function()
        {
            if ( !this.$Grid ) {
                return;
            }

            var Content = this.getContent(),
                size    = Content.getSize();

            this.$Grid.setHeight( size.y - 40 );
            this.$Grid.setWidth( size.x - 40 );

        }

    });

});