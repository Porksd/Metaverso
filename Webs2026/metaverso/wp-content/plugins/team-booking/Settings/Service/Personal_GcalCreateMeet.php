<?php

namespace VSHM\Settings\Service;

use VSHM\UI\Admin\Alert;
use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Dependency;

defined('ABSPATH') || exit;

/**
 * Class Personal_GcalCreateMeet
 *
 * @package VSHM
 */
class Personal_GcalCreateMeet extends ServicePersonalSettingBase
{
    public const ID = 'gcalCreateMeet';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Toggle::get(__('Create a Google Meet link', 'team-booking'), self::ID);
        $element->setDescription(__('A Google Meet link will be created after a reservation is made.', 'team-booking'));
        $element->setAlert(Alert::warning(__('In order for this feature to work, you need to set a destination Google Calendar.', 'team-booking')));
        $element->addDependency(Settings_Dependency::NOT_EQUAL('class', 'unscheduled'));
        $element->addDependency(Settings_Dependency::TRUTHY(Personal_GcalCreateEvent::ID));

        return $element;
    }

    public static function getDefault(): bool
    {
        return FALSE;
    }

    public static function whitelist($value, string $version)
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}