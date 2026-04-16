<?php

namespace VSHM\Settings\Reservation;

defined('ABSPATH') || exit;

/**
 * Class LocationOverride
 *
 * @package VSHM
 */
class LocationOverride extends ReservationSettingBase
{

    public const ID = 'locationOverride';

    public static function getDefault(): string
    {
        return '';
    }

}