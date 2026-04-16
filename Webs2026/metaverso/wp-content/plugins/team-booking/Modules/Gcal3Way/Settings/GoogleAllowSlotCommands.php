<?php

namespace VSHM\Modules\Gcal3Way\Settings;

use VSHM\UI\Admin\Alert;
use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class GoogleAllowSlotCommands
 */
class GoogleAllowSlotCommands extends GoogleSettingBase
{

    public const ID = 'googleAllowSlotCommands';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Toggle::get(__('Allow service providers to use "slot commands" to override some service settings directly from Google Calendar events.', 'team-booking'), self::ID);
        $element->setDescription(__('If active, service providers are be able to override some general service settings for specific slots, such as the price, by using "slot commands" directly in Google Calendar.', 'team-booking'));
        $element->setAlert(Alert::info(__('Administrators are always allowed.', 'team-booking')));

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