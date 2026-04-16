<?php

namespace VSHM\Settings\Reservation;

defined('ABSPATH') || exit;

/**
 * Class GoogleCalendarEventId
 *
 * @package VSHM
 */
class GoogleCalendarEventId extends ReservationSettingBase
{

    public const ID = 'gcalEventId';


    public static function getDefault(): string
    {
        return '';
    }

}