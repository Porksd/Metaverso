<?php

namespace VSHM\Settings;

use VSHM\UI\Admin\Alert;
use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class UseCache
 */
class UseCache extends SettingBase
{

    public const ID = 'useCache';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Toggle::get(__('Enable Caching', 'team-booking'), self::ID);
        $element->setDescription(__('If enabled, the plugin will utilize the existing caching system to improve loading times.', 'team-booking'));
        $element->setAlert(Alert::info(__('Disabling caching can significantly impact the overall speed of the plugin. Only disable caching if you are encountering problems with your current caching system.', 'team-booking')));

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