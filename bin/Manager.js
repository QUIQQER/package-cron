
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
                if ( !self.$Grid ) {
                    return;
                }

                var i, len, Btn;

                for ( i = 0, len = result.length; i < len; i++ )
                {
                    result[ i ].status = {
                        title  : 'Cron aktivieren / deaktivieren',
                        icon   : result[ i ].active ? 'icon-ok' : 'icon-remove',
                        cronId : result[ i ].id,
                        events :
                        {
                            onClick : function(Btn)
                            {
                                self.toggleStatusOfCron(
                                    Btn.getAttribute( 'cronId' )
                                );
                            }
                        }
                    };
                }

                self.$Grid.setData({
                    data : result
                });

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
            var self = this;

            this.addButton(
                new QUIButton({
                    text : 'Cron hinzufügen',
                    textimage : 'icon-plus',
                    events :
                    {
                        onClick : function() {
                            self.openAddCronWindow();
                        }
                    }
                })
            );

            this.addButton(
                new QUIButton({
                    text : 'Markierte Cron löschen',
                    textimage : 'icon-trash',
                    events :
                    {
                        onClick : function() {
                            self.deleteMarkedCrons();
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
                    dataIndex : 'status',
                    dataType  : 'button',
                    width     : 50
                }, {
                    header    : 'ID',
                    dataIndex : 'id',
                    dataType  : 'string',
                    width     : 50
                }, {
                    header    : 'Cron-Name',
                    dataIndex : 'title',
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
                    header    : 'Parameter',
                    dataIndex : 'params',
                    dataType  : 'string',
                    width     : 150
                }, {
                    header    : 'Cron-Beschreibung',
                    dataIndex : 'desc',
                    dataType  : 'string',
                    width     : 200
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
        },

        /**
         * Open the delete marked cron windows and delete all marked crons
         *
         * @return {self}
         */
        deleteMarkedCrons : function()
        {

            return this;
        },

        /**
         * Open the addCronWindow
         *
         * @return {self}
         */
        openAddCronWindow : function()
        {
            require(['package/quiqqer/cron/bin/AddCronWindow'], function(Window) {
                 new Window({}).open();
            });

            return this;
        },

        /**
         * Change the cron status
         * If the cron is active to deactive
         * If the cron is deactive to active
         *
         * @return {self}
         */
        toggleStatusOfCron : function(cronId)
        {
            Ajax.post('package_quiqqer_cron_ajax_cron_toggle', function(result)
            {

            }, {
                'package' : 'quiqqer/cron',
                cronId    : cronId
            });

            return this;
        }


    });

});