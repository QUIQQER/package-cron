
/**
 * Cron Manager
 *
 * @author www.pcsg.de (Henning Leutz)
 */

define('packages/cron/Manager', [

    'qui/controls/desktop/Panel'

],function(QUIPanel)
{
    "use static";

    new Class({

        Extends : QUIPanel,
        Type    : 'packages/cron/Manager',

        Binds : [
            '$onCreate'
        ],

        initialize : function(options)
        {
            this.parent( options );

            this.addEvents({
                onCreate : this.$onCreate
            });
        },

        /**
         * event : on Create
         */
        $onCreate : function()
        {



        }

    });

});