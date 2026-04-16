<?php

namespace VSHM\Settings;

use VSHM\UI\Admin\Alert;
use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class AllowCart
 */
class AllowCart extends SettingBase
{

    public const ID = 'allowCart';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Toggle::get(__('Allow multiple slots selection', 'team-booking'), self::ID);
        $element->setDescription(__('If enabled, customers will be able to extend the reservation duration by selecting consecutive time slots.', 'team-booking'));
        $element->setAlert(Alert::info(__('Please refer to the documentation for more information on the implications.', 'team-booking')));

        return $element;
    }

    public static function sanitize($value)
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public static function default(): bool
    {
        return FALSE;
    }
}