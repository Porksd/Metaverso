<?php

namespace VSHM\Settings\Service;

use VSHM\Providers\Objects\Service;
use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Dependency;
use VSHM\UI\Admin\Settings_Option;

defined('ABSPATH') || exit;

/**
 * Class Personal_EventColorBooked
 *
 * @package VSHM
 */
class Personal_EventColorBooked extends ServicePersonalSettingBase
{

    public const ID = 'eventColorBooked';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Radios::get(__('Event color (booked slot)', 'team-booking'), self::ID);
        $element->setColor(TRUE);
        $element->setDescription(__('Select a color for the Google Calendar reservation events.', 'team-booking'));

        $element->addOption(Settings_Option::get(__('Calendar default', 'team-booking'), 0));

        $color = Settings_Option::get('Lavender', 1);
        $color->setColor('#7986CB');
        $element->addOption($color);

        $color = Settings_Option::get('Sage', 2);
        $color->setColor('#33B679');
        $element->addOption($color);

        $color = Settings_Option::get('Grape', 3);
        $color->setColor('#8E24AA');
        $element->addOption($color);

        $color = Settings_Option::get('Flamingo', 4);
        $color->setColor('#E67C73');
        $element->addOption($color);

        $color = Settings_Option::get('Banana', 5);
        $color->setColor('#F6BF26');
        $element->addOption($color);

        $color = Settings_Option::get('Tangerine', 6);
        $color->setColor('#F4511E');
        $element->addOption($color);

        $color = Settings_Option::get('Peacock', 7);
        $color->setColor('#039BE5');
        $element->addOption($color);

        $color = Settings_Option::get('Graphite', 8);
        $color->setColor('#616161');
        $element->addOption($color);

        $color = Settings_Option::get('Blueberry', 9);
        $color->setColor('#3F51B5');
        $element->addOption($color);

        $color = Settings_Option::get('Basil', 10);
        $color->setColor('#0B8043');
        $element->addOption($color);

        $color = Settings_Option::get('Tomato', 11);
        $color->setColor('#D50000');
        $element->addOption($color);

        $element->addDependency(Settings_Dependency::NOT_EQUAL('class', 'unscheduled'));
        $element->addDependency(Settings_Dependency::TRUTHY(Personal_GcalCreateEvent::ID));

        return $element;
    }

    public static function getProcessedDefault(Service $service)
    {
        return 0;
    }

    public static function getDefault()
    {
        return 0;
    }

    public static function whitelist($value, string $version)
    {
        return (int)$value;
    }
}