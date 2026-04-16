<?php

namespace VSHM\Settings;

use VSHM\UI\Admin\Alert;
use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class LoadCalendarAtClosestSlot
 */
class LoadCalendarAtClosestSlot extends SettingBase
{

    public const ID = 'loadCalendarAtClosestSlot';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Toggle::get(__('Load the calendar on the nearest date with available slots', 'team-booking'), self::ID);
        $element->setDescription(__('If enabled, the frontend calendar will load on the nearest date that has at least one available slot.', 'team-booking'));
        $element->setAlert(Alert::warning(__('Enabling this option may result in slower page loading.', 'team-booking')));

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