<?php

namespace VSHM\Settings\Reservation;

defined('ABSPATH') || exit;

/**
 * Class Tickets
 *
 * @package VSHM
 */
class Tickets extends ReservationSettingBase
{

    public const ID = 'tickets';

    public static function getDefault(): int
    {
        return 1;
    }

}