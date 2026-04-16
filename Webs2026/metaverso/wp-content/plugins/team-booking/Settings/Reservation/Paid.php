<?php

namespace VSHM\Settings\Reservation;

defined('ABSPATH') || exit;

/**
 * Class Paid
 *
 * @package VSHM
 */
class Paid extends ReservationSettingBase
{
    public const ID = 'paid';

    public static function getDefault(): bool
    {
        return FALSE;
    }

}