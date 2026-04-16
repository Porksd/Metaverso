<?php

namespace VSHM\Settings\Reservation;

use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Implements some common setting methods.
 *
 * Defines an interface for a generic reservation setting.
 */
abstract class ReservationSettingBase
{
    public static function subscribe(): void
    {
        add_filter('vshm_default_reservation_settings', static function ($defaults, $slug) {
            if ($slug === vshm()->plugin['SLUG']) {
                $defaults[ static::ID ] = static::getDefault();
            }

            return $defaults;
        }, 10, 2);
    }

    public static function getBackendElement(): ?Element_Setting
    {
        return NULL;
    }

}
