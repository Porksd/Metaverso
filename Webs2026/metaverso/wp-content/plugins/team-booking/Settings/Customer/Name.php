<?php

namespace VSHM\Settings\Customer;

use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Dependency;

defined('ABSPATH') || exit;

/**
 * Class Name
 */
class Name extends CustomerSettingBase
{

    public const ID = 'name';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Input::get(__('Name', 'team-booking'), self::ID);
        $element->addDependency(Settings_Dependency::FALSY('wp_user'));

        return $element;
    }

    public static function default($resourceId): string
    {
        return '';
    }
}