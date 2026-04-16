<?php

namespace VSHM\Settings\Service;

use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Dependency;

defined('ABSPATH') || exit;

/**
 * Class Personal_DiscardOverlappingWithOther
 *
 * @package VSHM
 */
class Personal_DiscardOverlappingWithOther extends ServicePersonalSettingBase
{
    public const ID = 'keepOverlappingWithOther';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Toggle::get(__('Block availability if overlaps with reservations for other services', 'team-booking'), self::ID);
        $element->setDescription(__('Activate this option to automatically block all other overlapping available slots as soon as one of them is booked.', 'team-booking'));
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