<?php

namespace VSHM\Settings\Service;

use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Dependency;

defined('ABSPATH') || exit;

/**
 * Class Personal_SlotDuration
 *
 * @package VSHM
 */
class Personal_SlotDuration extends ServicePersonalSettingBase
{
    public const ID = 'p_slotDuration';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_DHM::get(__('Slot duration', 'team-booking'), self::ID);
        $element->showDays(FALSE);

        $element->addDependency(Settings_Dependency::EQUAL(Personal_SlotDurationRule::ID, Personal_SlotDurationRule::FIXED));
        $element->addDependency(Settings_Dependency::EQUAL(SlotDurationRule::ID, SlotDurationRule::PROVIDER));
        $element->addDependency(Settings_Dependency::NOT_EQUAL('class', 'unscheduled'));

        return $element;
    }

    public static function getDefault(): int
    {
        return 1 * HOUR_IN_SECONDS;
    }

    public static function whitelist($value, string $version)
    {
        return (int)$value;
    }
}