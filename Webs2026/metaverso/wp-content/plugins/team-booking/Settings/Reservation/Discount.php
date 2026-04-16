<?php

namespace VSHM\Settings\Reservation;

defined('ABSPATH') || exit;

/**
 * Class Discount
 *
 * @package VSHM
 */
class Discount extends ReservationSettingBase
{

    public const ID = 'discounts';

    public static function getDefault(): array
    {
        return [
            'name'   => '',
            'value'  => '',
            'type'   => '', // legacy: "percentage" or "direct"
            'id'     => '',
            'coupon' => ''
        ];
    }

}