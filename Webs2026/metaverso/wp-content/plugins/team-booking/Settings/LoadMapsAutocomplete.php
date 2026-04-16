<?php

namespace VSHM\Settings;

use VSHM\UI\Admin\Alert;
use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class LoadMapsAutocomplete
 */
class LoadMapsAutocomplete extends SettingBase
{

    public const ID = 'loadMapsAutocomplete';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Toggle::get(__('Use Google Places for address autocomplete', 'team-booking'), self::ID);
        $element->setDescription(__('Enable this setting if you want the Address field in the reservation form to be automatically completed using Google Places', 'team-booking'));
        $element->setAlert(Alert::warning(__('Please note that this may have a significant impact on your Google Cloud usage billing.', 'team-booking')));

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