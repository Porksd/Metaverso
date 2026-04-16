<?php

namespace VSHM\Settings\Service;

use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Dependency;

defined('ABSPATH') || exit;

/**
 * Class SlotDurationRule
 *
 * @package VSHM
 */
class SlotDurationRule extends ServiceSettingBase
{
    public const ID = 'slotDurationRule';

    public const        INHERITED = 'inherited';
    public const        FIXED     = 'fixed';
    public const        PROVIDER  = 'coworker';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Radios::get(__('Slot duration', 'team-booking'), self::ID);
        $element->setDescription(__('How the duration of a timeslot is defined', 'team-booking'));
        $element->addOption(\VSHM\UI\Admin\Settings_Option::get(__('Let Service Providers decide', 'team-booking'), self::PROVIDER));
        $element->addOption(\VSHM\UI\Admin\Settings_Option::get(__('Inherited from the entire available interval', 'team-booking'), self::INHERITED));
        $element->addOption(\VSHM\UI\Admin\Settings_Option::get(__('Fixed', 'team-booking'), self::FIXED));

        $element->addDependency(Settings_Dependency::NOT_EQUAL('class', 'unscheduled'));

        return $element;
    }

    public static function getDefault(): string
    {
        return self::PROVIDER;
    }
}