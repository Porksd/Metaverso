<?php

namespace VSHM\Settings\Service;

use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Dependency;

defined('ABSPATH') || exit;

/**
 * Class Personal_DiscardOverlappingWithSame
 *
 * @package VSHM
 */
class Personal_DiscardOverlappingWithSame extends ServicePersonalSettingBase
{
    public const ID = 'keepOverlappingWithSame';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Toggle::get(__('Block availability if overlaps with reservations for the same service', 'team-booking'), self::ID);
        $element->setDescription(__('If you are using multiple availability sources for the same service, activating this option will ensure that overlapping availabilities are excluded as soon as a reservation is made. This option is only valid if the sources are not independent.', 'team-booking'));
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