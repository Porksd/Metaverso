<?php

namespace VSHM\Modules\Zoom\Settings;

use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class ZoomAccountId
 */
class ZoomAccountId extends ZoomSettingBase
{
    public const ID = 'zoomAccountId';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Input::get(__('Zoom Account Id', 'team-booking'), self::ID);

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