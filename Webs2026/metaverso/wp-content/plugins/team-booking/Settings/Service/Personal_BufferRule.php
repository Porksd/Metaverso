<?php

namespace VSHM\Settings\Service;

use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Dependency;

defined('ABSPATH') || exit;

/**
 * Class Personal_BufferRule
 *
 * @package VSHM
 */
class Personal_BufferRule extends ServicePersonalSettingBase
{
    public const ID = 'bufferRule';

    public const ALWAYS = 'always';
    public const AFTER  = 'after_reservation';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Radios::get(__('Buffer configuration', 'team-booking'), self::ID);
        $element->setDescription(__('Select how the buffer between time slots should be calculated', 'team-booking'));
        $option = \VSHM\UI\Admin\Settings_Option::get(__('Always computed between slots', 'team-booking'), self::ALWAYS);
        $option->setDescription(__('The buffer is calculated between both free and booked slots within a container interval. This option is the default and ensures a consistent distribution of slots.', 'team-booking'));
        $element->addOption($option);
        $option = \VSHM\UI\Admin\Settings_Option::get(__('Only computed around booked slots', 'team-booking'), self::AFTER);
        $option->setDescription(__('The buffer is applied around booked slots. It can cause redistribution of free slots when a new reservation is made.', 'team-booking'));
        $element->addOption($option);

        $element->addDependency(Settings_Dependency::NOT_EQUAL(Personal_BufferTimespan::ID, 0));
        $element->addDependency(Settings_Dependency::NOT_EQUAL('class', 'unscheduled'));
        $element->addDependency(Settings_Dependency::OR_GROUP([
            Settings_Dependency::EQUAL(SlotDurationRule::ID, SlotDurationRule::FIXED),
            Settings_Dependency::AND_GROUP([
                Settings_Dependency::EQUAL(SlotDurationRule::ID, SlotDurationRule::PROVIDER),
                Settings_Dependency::EQUAL(Personal_SlotDurationRule::ID, Personal_SlotDurationRule::FIXED),
            ])
        ]));

        return $element;
    }

    public static function getDefault(): string
    {
        return self::ALWAYS;
    }
}