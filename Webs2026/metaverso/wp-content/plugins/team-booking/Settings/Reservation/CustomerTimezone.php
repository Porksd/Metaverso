<?php

namespace VSHM\Settings\Reservation;

use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class CustomerTimezone
 *
 * @package VSHM
 */
class CustomerTimezone extends ReservationSettingBase
{

    public const ID = 'customerTimezone';


    public static function getDefault(): string
    {
        return '';
    }

}