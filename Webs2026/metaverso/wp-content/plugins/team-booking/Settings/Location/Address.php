<?php

namespace VSHM\Settings\Location;

use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class Address
 */
class Address extends LocationSettingBase
{

    public const ID = 'address';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Input::get(__('Address', 'team-booking'), self::ID);

        return $element;
    }

    public static function default($resourceId): string
    {
        return '';
    }
}