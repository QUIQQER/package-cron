
/**
 * Cron Manager
 *
 * @module package/quiqqer/cron/bin/Manager
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/desktop/Panel
 * @require qui/controls/windows/Confirm
 * @require qui/controls/buttons/Button
 * @require qui/controls/buttons/Seperator
 * @require controls/grid/Grid
 * @require Ajax
 * @require Locale
 */

define('package/quiqqer/cron/bin/Manager', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'qui/controls/windows/Confirm',
    'qui/controls/buttons/Button',
    'qui/controls/buttons/Seperator',
    'controls/grid/Grid',
    'Ajax',
    'Locale'

],function(QUI, QUIPanel, QUIConfirm, QUIButton, QUIButtonSeperator, Grid, Ajax, Locale)
{
    "use strict";

    return new Class({

        Extends : QUIPanel,
        Type    : 'package/quiqqer/cron/bin/Manager',

        Binds : [
            '$onCreate',
            '$onResize'
        ],

        options : {
            title : 'Cron-Manager',
            icon  : 'icon-time'
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

                var execCron = function(Btn)
                {
                    self.execCron(
                        Btn.getAttribute( 'cronId' )
                    );
                };

                var toggleCron = function(Btn)
                {
                    self.toggleStatusOfCron(
                        Btn.getAttribute( 'cronId' )
                    );
                };

                for ( var i = 0, len = result.length; i < len; i++ )
                {
                    result[ i ].status = {
                        title  : 'Cron aktivieren / deaktivieren',
                        icon   : result[ i ].active == 1 ? 'icon-ok' : 'icon-remove',
                        cronId : result[ i ].id,
                        events : {
                            onClick : toggleCron
                        }
                    };

                    result[ i ].play = {
                        name   : 'cron-play-button-'+ result[ i ].id,
                        title  : 'Cron ausführen',
                        icon   : 'icon-play',
                        cronId : result[ i ].id,
                        events : {
                            onClick : execCron
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
                    name : 'add',
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

            this.addButton( new QUIButtonSeperator() );

            this.addButton(
                new QUIButton({
                    name : 'edit',
                    text : 'Markierten Cron editieren',
                    textimage : 'icon-pencil',
                    events :
                    {
                        onClick : function() {
                            self.editMarkedCron();
                        }
                    }
                })
            );

            this.addButton(
                new QUIButton({
                    name : 'delete',
                    text : 'Markierte(n) Cron(s) löschen',
                    textimage : 'icon-trash',
                    events :
                    {
                        onClick : function() {
                            self.deleteMarkedCrons();
                        }
                    }
                })
            );

            this.addButton( new QUIButtonSeperator() );

            this.addButton(
                new QUIButton({
                    name : 'history',
                    text : 'Cron History',
                    textimage : 'icon-long-arrow-right',
                    events :
                    {
                        onClick : function() {
                            self.showHistory();
                        }
                    }
                })
            );

            this.getButtons( 'edit' ).disable();
            this.getButtons( 'delete' ).disable();


            var Content = this.getContent(),

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
                    width     : 60
                }, {
                    header    : '&nbsp;',
                    dataIndex : 'play',
                    dataType  : 'button',
                    width     : 60
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
                    header    : 'Exec',
                    dataIndex : 'exec',
                    dataType  : 'string',
                    width     : 150
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
                }],
                multipleSelection : true,
                pagination : true
            });

            this.$Grid.addEvents({
                onRefresh : function() {
                    self.loadCrons();
                },
                onClick : function()
                {
                    var delButton  = self.getButtons( 'delete' ),
                        editButton = self.getButtons( 'edit' ),
                        selected   = self.$Grid.getSelectedIndices().length;

                    if ( selected == 1 )
                    {
                        editButton.enable();
                    } else
                    {
                        editButton.disable();
                    }

                    if ( selected )
                    {
                        delButton.enable();
                    } else
                    {
                        delButton.disable();
                    }
                },

                onDblClick : function(data)
                {
                    var rowData = self.$Grid.getDataByRow( data.row );

                    self.editCron( rowData.id );
                }
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
            if ( !this.$Grid ) {
                return this;
            }

            var self = this,
                data = this.$Grid.getSelectedData();

            if ( !data.length ) {
                return this;
            }

            var ids = data.map(function(o) {
                return o.id;
            });

            new QUIConfirm({
                icon : 'icon-remove',
                title : 'Cron Einträge löschen',
                text : '<p>Möchten Sie folgende Cron Einträge wirklich löschen?</p>',
                information : '<p>Zu löschende Cron-Einträge: <b>'+ ids.join(',') +'</b></p>' +
                              '<p>Beachten Sie, die Cron-Einträge sind nicht wieder herstellbar</p>',
                events :
                {
                    onSubmit : function(Win)
                    {
                        Win.Loader.show();

                        Ajax.post('package_quiqqer_cron_ajax_delete', function()
                        {
                            Win.close();
                            self.loadCrons();
                        }, {
                            'package' : 'quiqqer/cron',
                            ids : JSON.encode( ids )
                        });
                    }
                }
            }).open();

            return this;
        },

        /**
         * Edit a cron, opens the cron Edit-Window
         *
         * @param {String} cronId - ID of the Cron
         */
        editCron : function(cronId)
        {
            var self = this;

            require(['package/quiqqer/cron/bin/CronWindow'], function(Window)
            {
                 new Window({
                     cronId : cronId,
                     events :
                     {
                         onSubmit : function() {
                             self.loadCrons();
                         }
                     }
                 }).open();
            });

            return this;
        },

        /**
         * Opens the Edit-Window for the marked cron
         */
        editMarkedCron : function()
        {
            if ( !this.$Grid ) {
                return this;
            }

            var data = this.$Grid.getSelectedData();

            if ( !data.length ) {
                return this;
            }

            this.editCron( data[ 0 ].id );
        },

        /**
         * Open the add Cron-Window
         *
         * @return {self}
         */
        openAddCronWindow : function()
        {
            var self = this;

            require(['package/quiqqer/cron/bin/CronWindow'], function(Window)
            {
                 new Window({
                     events : {
                         onSubmit : function() {
                             self.loadCrons();
                         }
                     }
                 }).open();
            });

            return this;
        },

        /**
         * Change the cron status
         * If the cron is active to deactive
         * If the cron is deactive to active
         *
         * @param {Number} cronId - ID of the Cron
         * @return {self}
         */
        toggleStatusOfCron : function(cronId)
        {
            var self = this;

            Ajax.post('package_quiqqer_cron_ajax_cron_toggle', function()
            {
                self.loadCrons();
            }, {
                'package' : 'quiqqer/cron',
                cronId    : cronId
            });

            return this;
        },

        /**
         * Execute the cron
         *
         * @param {Number} cronId - ID of the Cron
         * @return {self}
         */
        execCron : function(cronId)
        {
            var i, len;
            var buttons = [];

            if ( this.$Grid ) {
                buttons = QUI.Controls.get( 'cron-play-button-'+ cronId );
            }

            for ( i = 0, len = buttons.length; i < len; i++ ) {
                buttons[ i ].setAttribute('icon', 'icon-refresh icon-spin');
            }

            Ajax.post('package_quiqqer_cron_ajax_cron_executeCron', function()
            {
                for ( i = 0, len = buttons.length; i < len; i++ ) {
                    buttons[ i ].setAttribute('icon', 'icon-play');
                }

            }, {
                'package' : 'quiqqer/cron',
                cronId    : cronId
            });
        },

        /**
         * Show the Cron-History Panel
         */
        showHistory : function()
        {
            var self = this;

            require(['package/quiqqer/cron/bin/History'], function(Panel) {
                new Panel().inject( self.getParent() );
            });
        }
    });

});