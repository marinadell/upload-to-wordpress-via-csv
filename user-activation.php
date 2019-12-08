<?php

namespace FsrImporter;


/**
 * Add support for the activation code query parameter.
 */
add_filter( 'query_vars', '\FsrImporter\query_vars_filter' );
function query_vars_filter( $vars )
{
  $vars[] .= 'fsrcode';
  return $vars;
}


/**
 * Get a full activiation URL for a user. The activation link will be
 * for the activation page specified in this plugin's settings.
 */
function get_activation_link( \WP_user $user=null ): ?string
{
    $slug = \FsrImporter\get_setting(
        \FsrImporter\SETTINGS_MAIN_OPTION_GROUP,
        \FsrImporter\SETTING_ACTIVATION_PAGE
    );

    if ( !$slug ) {
        return null;
    }

    if ( !$user ) {
        return null;
    }

    $code = get_user_meta( $user->ID, 'activation_code', true );

    if ( !$code ) {
        return null;
    }

    $url           = get_permalink( get_page_by_path( $slug ) );
    $url_with_code = esc_url( add_query_arg( 'fsrcode', $code, $url ) );

    return $url_with_code;
}


/**
 * Clear out the activation code for a user.
 */
function clear_activation_code( int $user_id ): void
{
    $user = $get_user_by( 'id', $user_id );
    if ( !$user ) {
        return;
    }

    update_user_meta( $user_id, 'activation_code', '' );
}


/**
 * Send an activation notification email to the email address specfified in the plugin settings. The
 * notification will describe the activation of $user.
 */
function send_activiation_notification( \WP_user $user ): void
{
    $to      = get_setting( SETTINGS_ACTIVATION_OPTION_GROUP, SETTING_ACTIVIATION_EMAIL );
    $subject = get_setting( SETTINGS_ACTIVATION_OPTION_GROUP, SETTING_ACTIVIATION_SUBJECT );
    $fsr_id  = get_user_meta( $user->ID, 'operator_fsr_id', true );

    $body  = '<div class="email-body">';
    $body .= get_setting( SETTINGS_ACTIVATION_OPTION_GROUP, SETTING_ACTIVIATION_BODY );
    $body .= "<p>Activated user: {$user->user_email}</p>";
    $body .= "<p>FSR Operator ID: {$fsr_id}</p>";
    $body .= "</div>";

    wp_mail( $to, $subject, $body );
}