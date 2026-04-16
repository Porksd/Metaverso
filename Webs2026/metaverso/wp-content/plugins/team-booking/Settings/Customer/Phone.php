<?php

namespace VSHM\Settings\Customer;

use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class Phone
 */
class Phone extends CustomerSettingBase
{

    public const ID = 'phone';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Phone::get(__('Phone', 'team-booking'), self::ID);

        return $element;
    }

    public static function default($resourceId): string
    {
        return '';
    }
}