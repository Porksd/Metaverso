<?php

namespace VSHM\Settings;

use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class FrontendTimezone
 */
class FrontendTimezone extends SettingBase
{

    public const ID = 'frontendTimezone';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_SelectTimezone::get(__('Frontend Timezone', 'team-booking'), self::ID);
        $element->setDescription(__("Select your preferred timezone for displaying date and times in the frontend. By default, date and times are displayed in customer's local timezone.", 'team-booking'));

        return $element;
    }

    public static function default(): bool
    {
        return FALSE;
    }
}