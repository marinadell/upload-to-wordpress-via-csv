<?php

namespace FsrImporter;

/**
* Plugin Name: BI Worldwide Account Importer
* Plugin URI: https://www.clockwork.com/
* Description: This plugin provides account import features from BI to WordPress.
* Version: 2.5
* Author: Amareya Allen-Dabney and Clockwork
* Author URI: http://clockwork.com/
**/

require_once( plugin_dir_path( __FILE__ ) . 'vendor/autoload.php' );

require_once( plugin_dir_path( __FILE__ ) . 'settings-page.php' );
require_once( plugin_dir_path( __FILE__ ) . 'user-importer.php' );
require_once( plugin_dir_path( __FILE__ ) . 'user-activation.php' );
require_once( plugin_dir_path( __FILE__ ) . 'background-task.php' );


/**
 * Create a new cron time interval.
 */
add_filter( 'cron_schedules', '\FsrImporter\cron_add_every_week' );
function cron_add_every_week( $schedules )
{
   $schedules['error_email_every_tuesday'] = array(
        'interval' => 30,
        'display'  => __( 'Once A week' )
   );
   return $schedules;
}


register_activation_hook( __FILE__, '\FsrImporter\schedule_background_processing' );
function schedule_background_processing(  )
{
    if ( wp_next_scheduled ( 'weekly_error_email' ) ) {
        return;
    }

    wp_schedule_event( time(), 'error_email_every_tuesday', 'weekly_error_email' );
}


register_deactivation_hook( __FILE__, '\FsrImporter\deactivate_background_processing' );
function deactivate_background_processing(  )
{
    wp_clear_scheduled_hook( 'weekly_error_email' );
}