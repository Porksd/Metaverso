<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


/**
 * Add File Restriction into Add-ons System
 *
 * - if it is not a first PB installation the File Restriction add-on may not appear in "wppb_module_settings" meta
 *
 */
function wppb_file_restriction_update_modules_list(){
    $wppb_module_settings = get_option( 'wppb_module_settings', 'not_found' );

    if ( $wppb_module_settings != 'not_found' ) {
        if ( !isset( $wppb_module_settings['wppb_fileRestriction'] ) )
            $wppb_module_settings['wppb_fileRestriction'] = 'hide';

        update_option( 'wppb_module_settings', $wppb_module_settings );
    }

}
add_action( 'admin_init', 'wppb_file_restriction_update_modules_list' );


/**
 * Detect web_server in use
 *
 */
function wppb_file_restriction_get_web_server() {

    if ( isset( $_SERVER['SERVER_SOFTWARE'] ) ) {

        $server_software = strtolower( sanitize_text_field( $_SERVER['SERVER_SOFTWARE'] ) );

        if ( strpos( $server_software, 'apache' ) !== false ) {
            return 'apache';
        } elseif ( strpos( $server_software, 'nginx' ) !== false ) {
            return 'nginx';
        } else {
            return 'unknown';
        }

    }

    return 'unknown';
}


/**
 * Add/Remove access rules when the File Restriction Add-On is activated or deactivated
 *
 */
function wppb_file_restriction_rewrite_htaccess_rules( $add_on_slug ) {

    if ( $add_on_slug !== 'wppb_fileRestriction' )
        return;

    $web_server = wppb_file_restriction_get_web_server();

    if ( $web_server === 'apache' ) {

        // reload the .htaccess file to update the File Restriction rules
        flush_rewrite_rules();

    }
    elseif ( $web_server === 'nginx' ) {

        // we need access to wppb_file_restriction_rewrite_nginx_rules() function to update the access rules on Add-On activation/deactivation
        require_once( WPPB_PAID_PLUGIN_DIR . '/add-ons-advanced/file-restriction/includes/file-restriction-admin.php' );

        // regenerate the File Restriction nginx config file
        wppb_file_restriction_rewrite_nginx_rules();

    }

}
add_action( 'wppb_add_ons_activate', 'wppb_file_restriction_rewrite_htaccess_rules' );
add_action( 'wppb_add_ons_deactivate', 'wppb_file_restriction_rewrite_htaccess_rules' );


/**
 * Add File Restriction settings if Add-On is active
 *
 */
if ( wppb_check_if_add_on_is_active( 'wppb_fileRestriction' ) ) {

    // Include the File Restriction admin settings file
    if ( file_exists( WPPB_PAID_PLUGIN_DIR .'/add-ons-advanced/file-restriction/includes/file-restriction-admin.php' ) ) {
        include_once( WPPB_PAID_PLUGIN_DIR .'/add-ons-advanced/file-restriction/includes/file-restriction-admin.php' );
    }

}


/**
 * Notify the user that Nginx web-server needs to be restarted
 *
 * - this admin notice is displayed when the File Restriction rules have been changed (activate/deactivate Add-On or File Restriction settings update)
 *
 */
function wppb_file_restriction_nginx_restart_notification() {

    // initiate the plugin notifications class
    $notifications = WPPB_Plugin_Notifications::get_instance();

    // this must be unique
    $notification_id = 'wppb_file_restriction_nginx_restart';

    // add notification text
    $notification_message = '<p style="font-size: 15px; margin-top:4px;">' . sprintf( __( 'The Nginx web server needs to be restarted for the new File Restriction rules to take effect. %1$sLearn more%2$s.', 'profile-builder' ), '<a href="https://www.cozmoslabs.com/docs/profile-builder/add-ons/files-restriction/?utm_source=wpbackend&utm_medium=clientsite&utm_content=file-restriction-restart-nginx-notice&utm_campaign=PBDocs#Nginx" target="_blank">', '</a>', '<br>' ) . '</p>';

    // set the PB icon
    $ul_icon_url = ( file_exists( WPPB_PLUGIN_DIR . 'assets/images/add-ons/pb-add-on-file-restriction-logo.png' )) ? WPPB_PLUGIN_URL . 'assets/images/add-ons/pb-add-on-file-restriction-logo.png' : '';
    $ul_icon = ( !empty($ul_icon_url)) ? '<img src="'. $ul_icon_url .'" width="64" height="64" style="float: left; margin: 15px 12px 15px 0; max-width: 100px;" alt="Profile Builder - Form Designs">' : '';

    // create the notification content
    $message = $ul_icon;
    $message .= '<h3 style="margin-bottom: 0;">Profile Builder PRO - File Restriction </h3>';
    $message .= $notification_message;
    $message .= '<a href="' . wp_nonce_url( add_query_arg( array( 'wppb_dismiss_admin_notification' => $notification_id ) ), 'wppb_plugin_notice_dismiss' ) . '" type="button" class="notice-dismiss"><span class="screen-reader-text">' . __( 'Dismiss this notice.', 'profile-builder' ) . '</span></a>';

    // add the notification  (we need to add the "notice is-dismissible" classes for the dismiss button to be correctly positioned)
    $notifications->add_notification( $notification_id, $message, 'wppb-notice error notice is-dismissible', false );
}

// display this notification only if Nginx is in use (we don't need this for Apache)
if ( wppb_check_if_add_on_is_active( 'wppb_fileRestriction' ) && wppb_file_restriction_get_web_server() === 'nginx' ) {

    $content_restriction_settings = get_option( 'wppb_content_restriction_settings', 'not_found' );

    // display this notification only if the File Restriction settings have been saved (Nginx restriction rule updated)
    if ( $content_restriction_settings != 'not_found' && ( isset( $content_restriction_settings['restrict_all_files'] ) || isset( $content_restriction_settings['restricted_file_types'] ) ) ) {
        add_action( 'admin_init', 'wppb_file_restriction_nginx_restart_notification' );
    }

}