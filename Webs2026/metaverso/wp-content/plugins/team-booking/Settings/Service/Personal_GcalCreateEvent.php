<?php

namespace VSHM\Settings\Service;

use VSHM\UI\Admin\Alert;
use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Dependency;

defined('ABSPATH') || exit;

/**
 * Class Personal_GcalCreateEvent
 *
 * @package VSHM
 */
class Personal_GcalCreateEvent extends ServicePersonalSettingBase
{
    public const ID = 'gcalCreateEvent';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Toggle::get(__('Add reservation on Google Calendar', 'team-booking'), self::ID);
        $element->setDescription(__('If enabled, reservations will be automatically added as events to the designated Google Calendar.', 'team-booking'));
        $element->setAlert(Alert::warning(__('In order for this feature to work, you need to set a destination Google Calendar.', 'team-booking')));
        $element->addDependency(Settings_Dependency::NOT_EQUAL('class', 'unscheduled'));

        return $element;
    }

    public static function getDefault(): bool
    {
        return TRUE;
    }

    public static function whitelist($value, string $version)
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}