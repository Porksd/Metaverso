<?php

namespace VSHM\Modules\Zoom\Settings;

use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class ZoomJWTApiSecret
 */
class ZoomJWTApiSecret extends ZoomSettingBase
{
    public const ID = 'zoomJWTApiSecret';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Input::get(__('Zoom JWT API secret (deprecated)', 'team-booking'), self::ID);

        return $element;
    }

    public static function sanitize($value)
    {
        return filter_var($value, FILTER_UNSAFE_RAW);
    }

    public static function default(): string
    {
        return '';
    }
}