<?php

namespace VSHM\Settings\Service;

use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Dependency;
use VSHM\UI\Admin\Settings_Option;

defined('ABSPATH') || exit;

/**
 * Class Personal_GcalReminder
 *
 * @package VSHM
 */
class Personal_GcalReminder extends ServicePersonalSettingBase
{
    public const ID = 'gcalReminder';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Radios::get(__('Set a Google Calendar reminder for reserved slot', 'team-booking'), self::ID);
        $element->addOption(Settings_Option::get(__('No reminder', 'team-booking'), 0));
        $element->addOption(Settings_Option::get(__('10 minutes before', 'team-booking'), 10));
        $element->addOption(Settings_Option::get(__('30 minutes before', 'team-booking'), 30));
        $element->addOption(Settings_Option::get(__('1 hour before', 'team-booking'), 60));
        $element->addDependency(Settings_Dependency::NOT_EQUAL('class', 'unscheduled'));
        $element->addDependency(Settings_Dependency::TRUTHY(Personal_GcalCreateEvent::ID));

        return $element;
    }

    public static function getDefault(): int
    {
        return 0;
    }

    public static function whitelist($value, string $version)
    {
        return (int)$value;
    }
}