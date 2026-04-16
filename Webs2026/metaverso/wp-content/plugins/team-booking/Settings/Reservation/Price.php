<?php

namespace VSHM\Settings\Reservation;

defined('ABSPATH') || exit;

/**
 * Class Price
 *
 * @package VSHM
 */
class Price extends ReservationSettingBase
{
    public const ID = 'price';

    public static function getDefault(): string
    {
        return '';
    }

}