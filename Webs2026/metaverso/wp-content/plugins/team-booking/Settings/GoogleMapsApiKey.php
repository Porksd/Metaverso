<?php

namespace VSHM\Settings;

use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class GoogleMapsApiKey
 */
class GoogleMapsApiKey extends SettingBase
{
    public const ID = 'googleMapsApiKey';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Input::get(__('Google Maps API key', 'team-booking'), self::ID);
        $element->setDescription(__("If you don't already have a Google Maps API key, please refer to the documentation for instructions on how to obtain one.", 'team-booking'));

        return $element;
    }

    public static function default(): string
    {
        return '';
    }
}