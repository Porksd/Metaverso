<?php

namespace VSHM\Settings\Service;

use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class ReservationFormId
 *
 * @package VSHM
 */
class ReservationFormId extends ServiceSettingBase
{
    public const ID = 'reservationFormId';

    public static function getBackendElement(): Element_Setting
    {
        // @NEXT
        $element = \VSHM\UI\Admin\Settings_Input::get('', self::ID);

        return $element;
    }

    public static function getDefault(): string
    {
        return '';
    }
}