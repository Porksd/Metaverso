<?php

namespace VSHM\Settings\Service;

use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class Personal_Participate
 *
 * @package VSHM
 */
class Personal_Participate extends ServicePersonalSettingBase
{
    public const ID = 'participate';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Toggle::get(__('Participate', 'team-booking'), self::ID);
        $element->setDescription(__('Turn this off if you want to exclude your availability events from being counted for this service. This can be useful for temporary needs, such as vacations, as it allows you to maintain your availability schedule unchanged.', 'team-booking'));

        return $element;
    }

    public static function getDefault(): bool
    {
        return TRUE;
    }

    public static function whitelist($value, string $version)
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}