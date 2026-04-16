<?php

defined('WP_UNINSTALL_PLUGIN') || exit;

foreach (wp_roles()->roles as $name => $role) {
    wp_roles()->remove_cap($name, 'tbk_can_admin');
    wp_roles()->remove_cap($name, 'tb_can_sync_calendar');
}

foreach (get_users() as $user) {
    /** @var $user WP_User */
    delete_user_option($user->ID, 'tbkUserPrefs');

    delete_user_meta($user->ID, 'tbk_AllowedServices');
    delete_user_meta($user->ID, 'tbk_ApiAccessToken');
    delete_user_meta($user->ID, 'tbk_GoogleAuthAccount');
    delete_user_meta($user->ID, 'tbk_GoogleAccessToken');
    delete_user_meta($user->ID, 'tbk_GoogleCalendars');
    delete_user_meta($user->ID, 'tbk_RestrictServices');
    delete_user_meta($user->ID, 'tbk_working_hours');
}

delete_option('tbk_version');
delete_option('tbk_migrated_from_v2');
delete_option('team-booking-options');
delete_option('tbk_gcal_2ways');
delete_option('tbk_zoom');
delete_option('tbk_paypal_settings');
delete_option('tbk_stripe_settings');

global $wpdb;
$prefix = $wpdb->prefix;

$tables = [
    'tbk_api_tokens',
    'tbk_customers',
    'tbk_uploaded_files',
    'tbk_form_entries',
    'tbk_form_fields',
    'tbk_forms',
    'tbk_gcal_events',
    'tbk_locations',
    'tbk_promotions',
    'tbk_reservations',
    'tbk_reservations_data',
    'tbk_provider_custom_data',
    'tbk_services',
    'tbk_services_data',
    'tbk_event_logs',
    'tbk_sessions',
    'tbk_gcal_cache'
];

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS {$prefix}{$table}");
}