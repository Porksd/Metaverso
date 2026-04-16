<?php

namespace VSHM\Settings\Service;

use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Dependency;

defined('ABSPATH') || exit;

/**
 * Class LocationAssigned
 *
 * @package VSHM
 */
class LocationAssigned extends ServiceSettingBase
{
    public const ID = 'locationAssigned';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_SelectLocation::get(__('Assigned location', 'team-booking'), self::ID);

        $element->addDependency(Settings_Dependency::EQUAL(Location::ID, 'fixed'));

        return $element;
    }

    public static function getDefault(): string
    {
        return '';
    }
}