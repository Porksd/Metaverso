<?php

namespace VSHM\Settings\Reservation;

defined('ABSPATH') || exit;

/**
 * Class Refund
 *
 * @package VSHM
 */
class Refund extends ReservationSettingBase
{

    public const ID = 'refund';

    public static function getDefault()
    {
        return NULL;
    }

}