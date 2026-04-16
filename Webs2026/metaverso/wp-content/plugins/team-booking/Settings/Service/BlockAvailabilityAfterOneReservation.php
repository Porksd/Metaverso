<?php

namespace VSHM\Settings\Service;

use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Dependency;

defined('ABSPATH') || exit;

/**
 * Class BlockAvailabilityAfterOneReservation
 *
 * @package VSHM
 */
class BlockAvailabilityAfterOneReservation extends ServiceSettingBase
{
    public const ID = 'blockAfterOne';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Toggle::get(__('Block after one reservation', 'team-booking'), self::ID);
        $element->setDescription(__('Even if a single reservation does not reach the maximum number of available tickets for the time slot(s), activating this setting will prevent other reservations from being made for the same time slot(s).', 'team-booking'));
        $element->addDependency(Settings_Dependency::NOT_EQUAL('class', 'unscheduled'));
        $element->addDependency(Settings_Dependency::NOT_EQUAL(TotalSlotTickets::ID, 1));

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