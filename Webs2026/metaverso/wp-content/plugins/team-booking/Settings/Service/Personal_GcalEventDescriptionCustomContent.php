<?php

namespace VSHM\Settings\Service;

use VSHM\UI\Admin\Alert;
use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Dependency;

defined('ABSPATH') || exit;

/**
 * Class Personal_GcalEventDescriptionCustomContent
 *
 * @package VSHM
 */
class Personal_GcalEventDescriptionCustomContent extends ServiceSettingBase
{
    public const ID = 'gcalEventDescriptionCustomContent';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Editor::get(__('Google Calendar event description custom content', 'team-booking'), self::ID);
        $element->setDescription(__('You can use dynamic placeholders.', 'team-booking'));
        $element->setAlert(Alert::warning(
            __('Do not use HTML content!', 'team-booking')
        ));
        $element->setUseTags(TRUE);
        $element->addDependency(Settings_Dependency::NOT_EQUAL('class', 'unscheduled'));
        $element->addDependency(Settings_Dependency::TRUTHY(Personal_GcalCreateEvent::ID));
        $element->addDependency(Settings_Dependency::EQUAL(Personal_GcalEventDescriptionContent::ID, Personal_GcalEventDescriptionContent::CUSTOM_CONTENT));

        return $element;
    }

    public static function getDefault(): string
    {
        return '';
    }
}