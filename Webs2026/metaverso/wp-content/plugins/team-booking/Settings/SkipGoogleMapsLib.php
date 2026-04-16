<?php

namespace VSHM\Settings;

use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class SkipGoogleMapsLib
 */
class SkipGoogleMapsLib extends SettingBase
{

    public const ID = 'skipGoogleMapsLib';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Toggle::get(__('Skip Google Maps library loading', 'team-booking'), self::ID);
        $element->setDescription(__('Enable this setting if you are experiencing issues with the Google Maps JS library being loaded by another plugin or theme.', 'team-booking'));

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