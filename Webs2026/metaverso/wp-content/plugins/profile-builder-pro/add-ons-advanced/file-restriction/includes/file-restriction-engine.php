<?php
// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Load WordPress environment
require_once( '../../../../../../wp-load.php' );

// get server request data
$wp_root = isset( $_SERVER['DOCUMENT_ROOT'] ) ? sanitize_text_field( $_SERVER['DOCUMENT_ROOT'] ) : '';
$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( $_SERVER['REQUEST_URI'] ) : '';
$http_referer = isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_text_field( $_SERVER['HTTP_REFERER'] ) : '';

// get the file path and url
$file_path = $wp_root . $request_uri;
$file_url = wppb_curpageurl();

// get the attachment data
$attachment_id = attachment_url_to_postid( $file_url );
$file_mime_type = mime_content_type( $file_path );

// check if the requested file is a WordPress generated file (mainly thumbnails or other WP generated image size)
if ( $attachment_id === 0 ) {
    $base_file_url = preg_replace('/-\d+x\d+(?=\.[^.]+$)/', '', $file_url);
    $attachment_id = attachment_url_to_postid( $base_file_url );
}

/**
 * Serve requested File when:
 *
 * - the request is made from WordPress Dashboard (media library thumbnails and single file view)
 * - there are no restrictions set for the attachment
 * - File Restriction Add-on is not active
 *
 */
if ( strpos( $http_referer, '/wp-admin/' ) !== false || !wppb_content_restriction_is_post_restricted( $attachment_id ) || !wppb_check_if_add_on_is_active( 'wppb_fileRestriction' ) ) {
    wppb_file_restriction_serve_file( $file_path, $file_mime_type );
}

// get the general Content Restriction settings
$wppb_content_restriction_settings = get_option( 'wppb_content_restriction_settings', 'not_found' );

// get the attachment and global restriction types
$attachment_restriction_type = get_post_meta( $attachment_id, 'wppb-content-restrict-type', true );
$settings_restriction_type = $wppb_content_restriction_settings['restrict_type'];

// handle attachment Default type restriction
if ( $attachment_restriction_type === 'default' )
    $attachment_restriction_type = $wppb_content_restriction_settings['restrict_type'];

// handle attachment Message type restriction
if ( $attachment_restriction_type === 'message' ) {
    $message = wppb_content_restriction_get_post_message( $attachment_id );

    // display the restriction message
    status_header( 403 );
    nocache_headers();
    die( wp_kses_post( $message ) );
}

// handle attachment Redirect type restriction
if ( $attachment_restriction_type === 'redirect' ) {
    $attachment_redirect_url_enabled = get_post_meta( $attachment_id, 'wppb-content-restrict-custom-redirect-url-enabled', true );
    $attachment_redirect_url = get_post_meta( $attachment_id, 'wppb-content-restrict-custom-redirect-url', true );
    $redirect_url = ( !empty( $attachment_redirect_url_enabled ) && !empty( $attachment_redirect_url ) ? $attachment_redirect_url : '' );

    // if a custom redirect URL is not set for the attachment, get the default redirect URL from general Content Restriction settings
    if ( empty( $redirect_url ) )
        $redirect_url = ( !empty( $wppb_content_restriction_settings['redirect_url'] ) ? $wppb_content_restriction_settings['redirect_url'] : '' );

    // if there is no redirect URL, serve the file
    if ( empty( $redirect_url ) )
        wppb_file_restriction_serve_file( $file_path, $file_mime_type );

    // redirect the user to the specific URL
    nocache_headers();
    wp_redirect( apply_filters( 'wppb_file_restriction_redirect_url', $redirect_url ) );
    exit;
}

/**
 * Serve requested File
 *
 */
function wppb_file_restriction_serve_file( $file_path, $file_mime_type ) {

    if ( file_exists( $file_path ) ) {

        header( 'Content-Type: ' . $file_mime_type );
        readfile( $file_path );
        exit;

    } else {

        status_header( 404 );
        nocache_headers();
        die( 'File not found.' );

    }

}