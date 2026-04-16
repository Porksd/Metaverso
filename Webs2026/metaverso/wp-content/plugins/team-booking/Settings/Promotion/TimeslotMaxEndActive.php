<?php

namespace VSHM\Settings\Promotion;

use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class TimeslotMaxEndActive
 */
class TimeslotMaxEndActive extends PromotionSettingBase
{

    public const ID = 'timeslotMaxEndActive';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Toggle::get(__('Time slot end date condition', 'team-booking'), self::ID);
        $element->setDescription(__("The promotion will be applied conditionally based on the time slot end date.", 'team-booking'));

        return $element;
    }

    public static function default($resourceId): bool
    {
        return FALSE;
    }
}