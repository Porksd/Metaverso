<?php

namespace VSHM\Settings\Service;

use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Dependency;

defined('ABSPATH') || exit;

/**
 * Class CreateZoomMeeting
 *
 * @package VSHM
 */
class CreateZoomMeeting extends ServiceSettingBase
{
    public const ID = 'createZoomMeeting';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Toggle::get(__('Create a Zoom Meeting', 'team-booking'), self::ID);
        $element->setDescription(__('Automatically create a Zoom Meeting after a reservation is made.', 'team-booking'));

        $element->addDependency(Settings_Dependency::NOT_EQUAL('class', 'unscheduled'));

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