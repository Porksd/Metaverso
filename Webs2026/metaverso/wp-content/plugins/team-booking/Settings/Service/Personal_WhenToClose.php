<?php

namespace VSHM\Settings\Service;

use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Dependency;
use VSHM\UI\Admin\Settings_Option;

defined('ABSPATH') || exit;

/**
 * Class Personal_WhenToClose
 *
 * @package VSHM
 */
class Personal_WhenToClose extends ServicePersonalSettingBase
{

    public const ID = 'whenToClose';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Select::get(__('When reservations should be closed?', 'team-booking'), self::ID);
        $element->setDescription(__('The system will not allow reservations to be made if the event start or end time is closer than the specified timespan.', 'team-booking'));

        for ($i = 0; $i <= 10; $i++) {
            $k = $i * 5;
            $element->addOption(Settings_Option::get(sprintf(
            /* translators: %d: number of minutes */
                _n('%d minute before', '%d minutes before', $k, 'team-booking'),
                $k
            ), 'PT' . $k . 'M'));
        }
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
        return 'PT0M';
    }
}