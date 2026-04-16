<?php

namespace VSHM\Settings\Service;

use VSHM\UI\Admin\Alert;
use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Dependency;

defined('ABSPATH') || exit;

/**
 * Class Personal_GcalAddGuests
 *
 * @package VSHM
 */
class Personal_GcalAddGuests extends ServicePersonalSettingBase
{
    public const ID = 'gcalAddGuests';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Toggle::get(__('Add customers as guests of the Google Calendar event', 'team-booking'), self::ID);
        $element->setDescription(__('If enabled, customers will receive Google notifications based on your Google Calendar settings. Additionally, a copy of the event will be created in their Google Calendars (if they have one), allowing them to view the event description. They will not have access to the guests list.', 'team-booking'));
        $element->setAlert(Alert::warning(__('In accordance with personal data regulations, it may be necessary to provide customers with a comprehensive disclosure regarding the storage of their data. Additionally, you should ensure the ability to erase such data upon request. Before configuring this option to store customer personal data outside of this site, it is advisable to consult with the site administrator to ensure compliance with relevant regulations and guidelines.', 'team-booking')));
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