<?php

namespace VSHM\Settings\Reservation;

defined('ABSPATH') || exit;

/**
 * Class CancellationOrDenyReason
 *
 * @package VSHM
 */
class CancellationOrDenyReason extends ReservationSettingBase
{
    public const ID = 'cancellationReason';

    public static function getDefault(): string
    {
        return '';
    }

}