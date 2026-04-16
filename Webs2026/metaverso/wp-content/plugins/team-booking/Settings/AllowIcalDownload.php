<?php

namespace VSHM\Settings;

use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class AllowIcalDownload
 */
class AllowIcalDownload extends SettingBase
{

    public const ID = 'allowIcalDownload';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Toggle::get(__('Allow customers to download iCal file after booking.', 'team-booking'), self::ID);

        return $element;
    }

    public static function sanitize($value)
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public static function default(): bool
    {
        return TRUE;
    }
}