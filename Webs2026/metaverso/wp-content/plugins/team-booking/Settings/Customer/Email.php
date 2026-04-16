<?php

namespace VSHM\Settings\Customer;

use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Dependency;

defined('ABSPATH') || exit;

/**
 * Class Email
 */
class Email extends CustomerSettingBase
{

    public const ID = 'email';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Input::get(__('Email', 'team-booking'), self::ID);
        $element->addDependency(Settings_Dependency::FALSY('wp_user'));

        return $element;
    }

    public static function default($resourceId): string
    {
        return '';
    }
}