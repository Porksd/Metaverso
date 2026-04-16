<?php

namespace VSHM\Settings\Service;

use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Dependency;

defined('ABSPATH') || exit;

/**
 * Class ShowTimes
 *
 * @package VSHM
 */
class ShowTimes extends ServiceSettingBase
{
    public const ID = 'showTimes';

    public const YES        = 'yes';
    public const NO         = 'no';
    public const START_ONLY = 'start_time_only';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Radios::get(__('Show start/end times', 'team-booking'), self::ID);
        $element->addOption(\VSHM\UI\Admin\Settings_Option::get(__('Show start/end', 'team-booking'), self::YES));
        $element->addOption(\VSHM\UI\Admin\Settings_Option::get(__('Hide', 'team-booking'), self::NO));
        $element->addOption(\VSHM\UI\Admin\Settings_Option::get(__('Show start time only', 'team-booking'), self::START_ONLY));

        $element->addDependency(Settings_Dependency::NOT_EQUAL('class', 'unscheduled'));

        return $element;
    }

    public static function getDefault(): string
    {
        return self::YES;
    }
}