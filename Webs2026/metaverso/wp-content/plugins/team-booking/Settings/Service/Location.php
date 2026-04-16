<?php

namespace VSHM\Settings\Service;

use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Dependency;

defined('ABSPATH') || exit;

/**
 * Class Location
 *
 * @package VSHM
 */
class Location extends ServiceSettingBase
{
    public const ID = 'location';

    public const NONE      = 'none';
    public const INHERITED = 'inherited';
    public const FIXED     = 'fixed';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Radios::get(__('Location', 'team-booking'), self::ID);
        $element->setDescription(__('Choose how to set the location for this service. If a location is provided, it will be displayed in the frontend with directions and a map, unless specified otherwise.', 'team-booking'));
        $element->addOption(\VSHM\UI\Admin\Settings_Option::get(__('No location', 'team-booking'), self::NONE));
        $element->addOption(\VSHM\UI\Admin\Settings_Option::get(__('Inherited from the booking form Address field', 'team-booking'), self::INHERITED));
        $element->addOption(\VSHM\UI\Admin\Settings_Option::get(__('Fixed', 'team-booking'), self::FIXED));

        $element->addDependency(Settings_Dependency::NOT_EQUAL('class', 'unscheduled'));

        return $element;
    }

    public static function getDefault(): string
    {
        return self::NONE;
    }
}