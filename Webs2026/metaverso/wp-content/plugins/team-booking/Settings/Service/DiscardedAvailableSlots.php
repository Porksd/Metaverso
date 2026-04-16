<?php

namespace VSHM\Settings\Service;

use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Dependency;

defined('ABSPATH') || exit;

/**
 * Class DiscardedAvailableSlots
 *
 * @package VSHM
 */
class DiscardedAvailableSlots extends ServiceSettingBase
{
    public const ID = 'discardedAvailableSlots';

    public const HIDE   = 'hide';
    public const BOOKED = 'booked';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Radios::get(__('How to treat discarded available slots', 'team-booking'), self::ID);
        $element->setDescription(__('Choose how to handle available slots that are discarded due to overlapping settings.', 'team-booking'));
        $element->addOption(\VSHM\UI\Admin\Settings_Option::get(__("Don't show", 'team-booking'), self::HIDE));

        // @NEXT: it doesn't work if the provider set the filling logic as ADAPTIVE
        $element->addOption(\VSHM\UI\Admin\Settings_Option::get(__('Show them as booked', 'team-booking'), self::BOOKED));

        $element->addDependency(Settings_Dependency::NOT_EQUAL('class', 'unscheduled'));
        $element->addDependency(Settings_Dependency::TRUTHY(ShowBookedSlots::ID));

        return $element;
    }

    public static function getDefault(): string
    {
        return self::HIDE;
    }
}