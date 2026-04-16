<?php

namespace VSHM\Settings\Service;

use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Dependency;
use VSHM\UI\Admin\Settings_Option;

defined('ABSPATH') || exit;

/**
 * Class Personal_WhenToOpen
 *
 * @package VSHM
 */
class Personal_WhenToOpen extends ServicePersonalSettingBase
{

    public const ID = 'whenToOpen';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Select::get(__('When reservations should be opened?', 'team-booking'), self::ID);
        $element->setDescription(__('When the start time of the time slot is within the specified timespan, the slot will be visible in the frontend.', 'team-booking'));

        $element->addOption(Settings_Option::get(__('Always', 'team-booking'), 'PT0H'));
        for ($i = 1; $i <= 24; $i++) {
            $element->addOption(Settings_Option::get(sprintf(
            /* translators: %d: number of hours */
                _n('%d hour before', '%d hours before', $i, 'team-booking'),
                $i
            ), 'PT' . $i . 'H'));
        }
        for ($i = 1; $i <= 30; $i++) {
            $element->addOption(Settings_Option::get(sprintf(
            /* translators: %d: number of days */
                _n('%d day before (until midnight)', '%d days before (until midnight)', $i, 'team-booking'),
                $i
            ), 'P' . $i . 'Dmid'));
        }
        for ($i = 2; $i <= 5; $i++) {
            $k = $i * 30;
            $element->addOption(Settings_Option::get(sprintf(
            /* translators: %d: number of days */
                _n('%d day before (until midnight)', '%d days before (until midnight)', $k, 'team-booking'),
                $k
            ), 'P' . $k . 'Dmid'));
        }
        $element->addDependency(Settings_Dependency::NOT_EQUAL('class', 'unscheduled'));

        return $element;
    }

    public static function getDefault(): string
    {
        return 'PT0H';
    }
}