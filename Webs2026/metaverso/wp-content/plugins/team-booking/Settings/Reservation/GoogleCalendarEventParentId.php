<?php

namespace VSHM\Settings\Reservation;

defined('ABSPATH') || exit;

/**
 * Class GoogleCalendarEventParentId
 *
 * @package VSHM
 */
class GoogleCalendarEventParentId extends ReservationSettingBase
{

    public const ID = 'gcalEventParentId';

    public static function getDefault(): string
    {
        return '';
    }

}