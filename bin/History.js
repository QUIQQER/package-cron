/**
 * Cron History Panel
 *
 * @author www.pcsg.de (Henning Leutz)
 */

define('package/quiqqer/cron/bin/History', [

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
        Type    : 'package/cron/bin/History',

        Binds : [
            '$onCreate',
            '$onResize'
        ],

        options : {
            title : 'Cron-History',
            icon : 'icon-long-arrow-right'
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
         * Load the History list
         */
        loadData : function()
        {
            var self = this;

            Ajax.get('package_quiqqer_cron_ajax_history_get', function(result)
            {
                self.$Grid.setData({
                    data : result
                });

            }, {
                'package' : 'quiqqer/cron'
            });
        },

        /**
         * event : on Create
         */
        $onCreate : function()
        {
            var self      = this,
                Content   = this.getContent(),
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
                    header    : '&nbsp;',
                    dataIndex : 'lastexec',
                    dataType  : 'date',
                    width     : 150
                }, {
                    header    : 'Cron-ID',
                    dataIndex : 'cronid',
                    dataType  : 'string',
                    width     : 100
                }, {
                    header    : 'User-ID',
                    dataIndex : 'uid',
                    dataType  : 'string',
                    width     : 150
                }],
                pagination : true
            });

            this.$Grid.addEvents({
                onRefresh : function() {
                    self.loadData();
                }
            });

            this.$Grid.refresh();
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