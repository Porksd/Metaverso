<?php

namespace VSHM\Modules\Zoom\Settings;

use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class ZoomApiKey
 */
class ZoomApiKey extends ZoomSettingBase
{
    public const ID = 'zoomApiKey';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Input::get(__('Zoom API key', 'team-booking'), self::ID);

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