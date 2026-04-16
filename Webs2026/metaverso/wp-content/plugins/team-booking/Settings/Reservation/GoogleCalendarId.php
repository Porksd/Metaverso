<?php

namespace VSHM\Settings\Reservation;

defined('ABSPATH') || exit;

/**
 * Class GoogleCalendarId
 *
 * @package VSHM
 */
class GoogleCalendarId extends ReservationSettingBase
{

    public const ID = 'gcalId';

    public static function getDefault(): string
    {
        return '';
    }

}