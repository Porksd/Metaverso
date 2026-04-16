<?php

namespace VSHM\Settings\Reservation;

defined('ABSPATH') || exit;

/**
 * Class GoogleMeetId
 *
 * @package VSHM
 */
class GoogleMeetId extends ReservationSettingBase
{

    public const ID = 'googleMeetId';

    public static function getDefault(): string
    {
        return '';
    }

}