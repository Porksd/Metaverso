<?php

namespace VSHM\Settings\Promotion;

use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class TimeslotMinStartActive
 */
class TimeslotMinStartActive extends PromotionSettingBase
{

    public const ID = 'timeslotMinStartActive';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Toggle::get(__('Time slot start date condition', 'team-booking'), self::ID);
        $element->setDescription(__('The promotion will be applied conditionally based on the time slot start date.', 'team-booking'));

        return $element;
    }

    public static function default($resourceId): bool
    {
        return FALSE;
    }
}