<?php

namespace VSHM\Settings\Service;

use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Dependency;

defined('ABSPATH') || exit;

/**
 * Class LocationVisibility
 *
 * @package VSHM
 */
class LocationVisibility extends ServiceSettingBase
{
    public const ID = 'locationVisibility';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Toggle::get(__('Location visibility', 'team-booking'), self::ID);
        $element->setDescription(__('Choose whether the location should be visible in the frontend. If you choose to hide the location from the frontend, it will still be visible in the backend and can be used, for example, in email templates.', 'team-booking'));

        $element->addDependency(Settings_Dependency::NOT_EQUAL(Location::ID, Location::NONE));
        $element->addDependency(Settings_Dependency::NOT_EQUAL('class', 'unscheduled'));

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