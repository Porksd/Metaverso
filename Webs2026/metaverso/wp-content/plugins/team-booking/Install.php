<?php

namespace VSHM;

use VSHM\Modules\EventLogger;
use VSHM\Providers\ApiTokens;
use VSHM\Providers\Customers;
use VSHM\Providers\Files;
use VSHM\Providers\FormEntries;
use VSHM\Providers\FormFields;
use VSHM\Providers\Forms;
use VSHM\Providers\Locations;
use VSHM\Providers\Promotions;
use VSHM\Providers\Reservations;
use VSHM\Providers\ReservationsData;
use VSHM\Providers\ServiceProviderCustomData;
use VSHM\Providers\Services;
use VSHM\Providers\ServicesData;
use VSHM\Settings\AllowedAdminWpRoles;
use VSHM\Settings\AllowedServiceProviderWpRoles;

defined('ABSPATH') || exit;

/**
 * Class Install
 *
 * @package VSHM
 * @author  VonStroheim
 */
class Install
{
    /**
     * @return bool
     */
    public static function plugin_install(): bool
    {
        /**
         * extra safeguard, the current user must have
         * activate_plugins capability
         */
        if (!current_user_can('activate_plugins')) {
            return FALSE;
        }

        /**
         * Set admin capability
         */
        foreach (wp_roles()->roles as $name => $role) {
            if ($name === 'administrator') {
                wp_roles()->add_cap($name, AllowedAdminWpRoles::ROLE);
                wp_roles()->add_cap($name, AllowedServiceProviderWpRoles::ROLE);
            }
        }

        /**
         * Set scheduled hooks
         */
        if (!wp_get_schedule('tbk_daily_cron')) {
            wp_schedule_event(time(), 'daily', 'tbk_daily_cron');
        }
        if (!wp_get_schedule('tbk_hourly_cron')) {
            wp_schedule_event(time(), 'hourly', 'tbk_hourly_cron');
        }
        if (!wp_get_schedule('tbk_weekly_cron')) {
            wp_schedule_event(time(), 'weekly', 'tbk_weekly_cron');
        }

        /**
         * Tables
         */
        Services::maybe_create_table();
        ServicesData::maybe_create_table();
        Reservations::maybe_create_table();
        ReservationsData::maybe_create_table();
        Promotions::maybe_create_table();
        Customers::maybe_create_table();
        ServiceProviderCustomData::maybe_create_table();
        Locations::maybe_create_table();
        Forms::maybe_create_table();
        Files::maybe_create_table();
        FormFields::maybe_create_table();
        FormEntries::maybe_create_table();
        ApiTokens::maybe_create_table();
        EventLogger::maybe_create_table();

        return TRUE;
    }

    /**
     * @return bool
     */
    public static function plugin_deactivate(): bool
    {
        /**
         * extra safeguard, the current user must have
         * activate_plugins capability
         */
        if (!current_user_can('activate_plugins')) {
            return FALSE;
        }

        /**
         * Remove the cronjobs
         */
        wp_unschedule_hook('tbk_daily_cron');
        wp_unschedule_hook('tbk_hourly_cron');
        wp_unschedule_hook('tbk_weekly_cron');

        do_action('tbk_deactivate');

        return TRUE;
    }
}