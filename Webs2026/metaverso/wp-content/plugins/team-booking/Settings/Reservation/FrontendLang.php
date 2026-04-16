<?php

namespace VSHM\Settings\Reservation;

defined('ABSPATH') || exit;

/**
 * Class FrontendLang
 *
 * @package VSHM
 */
class FrontendLang extends ReservationSettingBase
{

    public const ID = 'frontendLang';

    public static function getDefault(): string
    {
        return '';
    }

}