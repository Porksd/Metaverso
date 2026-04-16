<?php

namespace VSHM\Settings\Location;

use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class Name
 */
class Name extends LocationSettingBase
{

    public const ID = 'name';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Input::get(__('Name', 'team-booking'), self::ID);

        return $element;
    }

    public static function default($resourceId): string
    {
        return '';
    }
}