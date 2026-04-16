<?php

namespace VSHM\Settings\Reservation;

defined('ABSPATH') || exit;

/**
 * Class SlotId
 *
 * @package VSHM
 */
class SlotId extends ReservationSettingBase
{

    public const ID = 'slotId';

    public static function getDefault(): string
    {
        return '';
    }

}