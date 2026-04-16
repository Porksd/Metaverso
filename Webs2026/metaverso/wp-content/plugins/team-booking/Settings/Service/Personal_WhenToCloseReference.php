<?php

namespace VSHM\Settings\Service;

use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Dependency;
use VSHM\UI\Admin\Settings_Option;

defined('ABSPATH') || exit;

/**
 * Class Personal_WhenToCloseReference
 *
 * @package VSHM
 */
class Personal_WhenToCloseReference extends ServicePersonalSettingBase
{
    public const ID = 'whenToCloseReference';

    public const START = 'start';
    public const END   = 'end';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Select::get(__('Reference time', 'team-booking'), self::ID);

        $element->addOption(Settings_Option::get(__('Slot start time', 'team-booking'), self::START));
        $element->addOption(Settings_Option::get(__('Slot end time', 'team-booking'), self::END));
        $element->addDependency(Settings_Dependency::NOT_EQUAL('class', 'unscheduled'));

        return $element;
    }

    public static function getDefault(): string
    {
        return self::START;
    }
}