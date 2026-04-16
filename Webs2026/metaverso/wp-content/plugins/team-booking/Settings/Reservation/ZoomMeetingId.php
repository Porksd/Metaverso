<?php

namespace VSHM\Settings\Reservation;

defined('ABSPATH') || exit;

/**
 * Class ZoomMeetingId
 *
 * @package VSHM
 */
class ZoomMeetingId extends ReservationSettingBase
{

    public const ID = 'zoomMeetingId';

    public static function getDefault(): string
    {
        return '';
    }

}