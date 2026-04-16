<?php

namespace VSHM\Settings\Service;

use VSHM\Providers\Objects\Service;
use VSHM\UI\Admin\Alert;
use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Dependency;

defined('ABSPATH') || exit;

/**
 * Class Personal_EventTitleBooked
 *
 * @package VSHM
 */
class Personal_EventTitleBooked extends ServicePersonalSettingBase
{

    public const ID = 'eventTitleBooked';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Input::get(__('Event title (booked slot)', 'team-booking'), self::ID);
        $element->setDescription(__('Provide a title for the Google Calendar reservation events.', 'team-booking'));

        $element->addDependency(Settings_Dependency::NOT_EQUAL('class', 'unscheduled'));
        $element->addDependency(Settings_Dependency::TRUTHY(Personal_GcalCreateEvent::ID));

        return $element;
    }

    public static function getProcessedDefault(Service $service)
    {
        return sprintf(
        /* translators: %s: name of the service */
            __('New reservation for %s', 'team-booking'),
            $service->name
        );
    }

    public static function getDefault(): string
    {
        return '';
    }
}