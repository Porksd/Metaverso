<?php

namespace VSHM\Settings\Reservation;

defined('ABSPATH') || exit;

/**
 * Class Location
 *
 * @package VSHM
 */
class Location extends ReservationSettingBase
{

    public const ID = 'location';

    public static function getDefault(): string
    {
        return '';
    }

}