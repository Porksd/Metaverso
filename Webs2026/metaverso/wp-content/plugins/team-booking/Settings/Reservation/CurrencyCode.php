<?php

namespace VSHM\Settings\Reservation;

use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class CurrencyCode
 *
 * @package VSHM
 */
class CurrencyCode extends ReservationSettingBase
{

    public const ID = 'currencyCode';

    public static function getDefault(): string
    {
        return '';
    }

}