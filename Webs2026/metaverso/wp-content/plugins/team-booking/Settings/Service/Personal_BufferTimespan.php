<?php

namespace VSHM\Settings\Service;

use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Dependency;

defined('ABSPATH') || exit;

/**
 * Class Personal_BufferTimespan
 *
 * @package VSHM
 */
class Personal_BufferTimespan extends ServicePersonalSettingBase
{
    public const ID = 'bufferTimespan';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_DHM::get(__('Buffer between consecutive slots', 'team-booking'), self::ID);
        $element->showDays(FALSE);

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

    public static function getDefault(): int
    {
        return 0;
    }
}