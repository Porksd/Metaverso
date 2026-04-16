<?php

namespace VSHM\Settings\Service;

use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Dependency;

defined('ABSPATH') || exit;

/**
 * Class Personal_DiscardOverlappingWithPersonal
 *
 * @package VSHM
 */
class Personal_DiscardOverlappingWithPersonal extends ServicePersonalSettingBase
{
    public const ID = 'keepOverlappingWithPersonal';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Toggle::get(__('Block availability if overlaps with personal events', 'team-booking'), self::ID);
        $element->setDescription(__('Busy events in a personal Google Calendar will block the availability of this service. A personal Google Calendar which is different from the source one, must be set for this to work.', 'team-booking'));
        $element->addDependency(Settings_Dependency::NOT_EQUAL('class', 'unscheduled'));

        return $element;
    }

    public static function getDefault(): bool
    {
        return FALSE;
    }

    public static function whitelist($value, string $version)
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}