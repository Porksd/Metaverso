<?php

namespace VSHM\Modules\Zoom\Settings;

use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class ZoomAccessToken
 */
class ZoomAccessToken extends ZoomSettingBase
{
    public const ID = 'zoomAccessToken';

    public static function getBackendElement(): ?Element_Setting
    {
        return NULL;
    }

    public static function sanitize($value)
    {
        return filter_var($value, FILTER_UNSAFE_RAW);
    }

    public static function default(): ?string
    {
        return NULL;
    }
}