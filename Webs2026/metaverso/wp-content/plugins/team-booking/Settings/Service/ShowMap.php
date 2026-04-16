<?php

namespace VSHM\Settings\Service;

use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Dependency;

defined('ABSPATH') || exit;

/**
 * Class ShowMap
 *
 * @package VSHM
 */
class ShowMap extends ServiceSettingBase
{
    public const ID = 'showMap';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Toggle::get(__('Show map', 'team-booking'), self::ID);
        $element->setDescription(__('By default, a location map is displayed. However, you can change this setting to hide the map if it is not needed.', 'team-booking'));

        $element->addDependency(Settings_Dependency::NOT_EQUAL(Location::ID, Location::NONE));
        $element->addDependency(Settings_Dependency::TRUTHY(LocationVisibility::ID));
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