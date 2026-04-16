<?php

namespace VSHM\Settings\Promotion;

use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Dependency;

defined('ABSPATH') || exit;

/**
 * Class TimeslotMaxEnd
 */
class TimeslotMaxEnd extends PromotionSettingBase
{
    public const ID = 'timeslotMaxEnd';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Date::get(__('Maximum end date for time slots', 'team-booking'), self::ID);
        $element->setDescription(__('The promotion is valid for time slots that end on or before this date.', 'team-booking'));
        $element->addDependency(Settings_Dependency::TRUTHY(TimeslotMaxEndActive::ID));

        return $element;
    }

    public static function default($resourceId): string
    {
        return '';
    }
}