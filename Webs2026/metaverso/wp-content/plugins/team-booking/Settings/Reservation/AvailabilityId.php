<?php

namespace VSHM\Settings\Reservation;

use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class AvailabilityId
 *
 * @package VSHM
 */
class AvailabilityId extends ReservationSettingBase
{

    public const ID = 'availabilityId';

    public static function getDefault(): string
    {
        return '';
    }

}