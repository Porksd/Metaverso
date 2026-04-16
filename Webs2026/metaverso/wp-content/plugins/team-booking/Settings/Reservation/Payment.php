<?php

namespace VSHM\Settings\Reservation;

defined('ABSPATH') || exit;

/**
 * Class Payment
 *
 * @package VSHM
 */
class Payment extends ReservationSettingBase
{

    public const ID = 'payment';

    public static function getDefault(): array
    {
        return [
            'name'   => '',
            'value'  => '',
            'type'   => '',
            'id'     => '',
            'coupon' => ''
        ];
    }

}