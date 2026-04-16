<?php

namespace VSHM\Settings\Service;

use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Dependency;

defined('ABSPATH') || exit;

/**
 * Class Personal_OverlappingWithSameDropTickets
 *
 * @package VSHM
 */
class Personal_OverlappingWithSameDropTickets extends ServicePersonalSettingBase
{
    public const ID = 'overlappingWithSameDropTickets';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Toggle::get(__('Drop tickets', 'team-booking'), self::ID);
        $element->setDescription(__('Instead of dropping the slot, reservations made for the same service but in a different availability plan will be treated as if they are on the same plan.', 'team-booking'));
        $element->addDependency(Settings_Dependency::NOT_EQUAL('class', 'unscheduled'));
        $element->addDependency(Settings_Dependency::TRUTHY(Personal_DiscardOverlappingWithSame::ID));

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