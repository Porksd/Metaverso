<?php

namespace VSHM\Settings\Service;

use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Dependency;

defined('ABSPATH') || exit;

/**
 * Class Personal_FillingLogic
 *
 * @package VSHM
 */
class Personal_FillingLogic extends ServicePersonalSettingBase
{
    public const ID = 'slotFillingLogic';

    public const FIXED    = 'fixed';
    public const ADAPTIVE = 'adaptive';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Radios::get(__('Slots filling', 'team-booking'), self::ID);
        $element->setDescription(__('Choose how the slots should be redistributed when a blocking event or a reservation of other services occurs.', 'team-booking'));
        $option = \VSHM\UI\Admin\Settings_Option::get(__('Fixed', 'team-booking'), self::FIXED);
        $option->setDescription(__('Slots will always observe the original segmentation', 'team-booking'));
        $element->addOption($option);
        $option = \VSHM\UI\Admin\Settings_Option::get(__('Adaptive', 'team-booking'), self::ADAPTIVE);
        $option->setDescription(__('Slots are adapted to maximize the available times', 'team-booking'));
        $element->addOption($option);

        $element->addDependency(Settings_Dependency::NOT_EQUAL('class', 'unscheduled'));
        $element->addDependency(Settings_Dependency::OR_GROUP([
            Settings_Dependency::EQUAL(SlotDurationRule::ID, SlotDurationRule::FIXED),
            Settings_Dependency::AND_GROUP([
                Settings_Dependency::EQUAL(SlotDurationRule::ID, SlotDurationRule::PROVIDER),
                Settings_Dependency::EQUAL(Personal_SlotDurationRule::ID, Personal_SlotDurationRule::FIXED),
            ])
        ]));
        $element->addDependency(Settings_Dependency::OR_GROUP([
            Settings_Dependency::TRUTHY(Personal_DiscardOverlappingWithOther::ID),
            Settings_Dependency::TRUTHY(Personal_DiscardOverlappingWithPersonal::ID),
        ]));

        return $element;
    }

    public static function getDefault(): string
    {
        return self::FIXED;
    }
}