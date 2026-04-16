<?php

namespace VSHM\Settings\Promotion;

use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Dependency;

defined('ABSPATH') || exit;

/**
 * Class TimeslotMinStart
 */
class TimeslotMinStart extends PromotionSettingBase
{
    public const ID = 'timeslotMinStart';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Date::get(__('Minimum start date for time slots', 'team-booking'), self::ID);
        $element->setDescription(__('The promotion is valid for time slots that start on or after this date.', 'team-booking'));
        $element->addDependency(Settings_Dependency::TRUTHY(TimeslotMinStartActive::ID));

        return $element;
    }

    public static function default($resourceId): string
    {
        return '';
    }
}