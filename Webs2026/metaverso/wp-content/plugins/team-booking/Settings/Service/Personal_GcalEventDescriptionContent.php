<?php

namespace VSHM\Settings\Service;

use VSHM\UI\Admin\Alert;
use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Dependency;

defined('ABSPATH') || exit;

/**
 * Class Personal_GcalEventDescriptionContent
 *
 * @package VSHM
 */
class Personal_GcalEventDescriptionContent extends ServicePersonalSettingBase
{
    public const ID             = 'gcalEventDescriptionContent';

    public const EMPTY          = 'empty';
    public const CUSTOMER_DATA  = 'customer_data';
    public const CUSTOM_CONTENT = 'custom_content';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Radios::get(__('Google Calendar event description content', 'team-booking'), self::ID);
        $element->setDescription(__('Specify the content that should be included in the event description of the Google Calendar when a reservation is made.', 'team-booking'));
        $option = \VSHM\UI\Admin\Settings_Option::get(__('Empty content', 'team-booking'), self::EMPTY);
        $element->addOption($option);
        $option = \VSHM\UI\Admin\Settings_Option::get(__("Customer's name, tickets, email and phone when available", 'team-booking'), self::CUSTOMER_DATA);
        $element->addOption($option);
        $option = \VSHM\UI\Admin\Settings_Option::get(__("Custom content", 'team-booking'), self::CUSTOM_CONTENT);
        $element->addOption($option);

        $element->setAlert(Alert::warning(__('Privacy alert: Please note that the content of the Google Calendar event description may be visible to guests if customers are being added as guests.', 'team-booking')));

        $element->addDependency(Settings_Dependency::NOT_EQUAL('class', 'unscheduled'));
        $element->addDependency(Settings_Dependency::TRUTHY(Personal_GcalCreateEvent::ID));

        return $element;
    }

    public static function getDefault(): string
    {
        return self::EMPTY;
    }
}