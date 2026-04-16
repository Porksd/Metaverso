<?php

namespace VSHM\Settings\Promotion;

use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class PromotionPeriod
 */
class PromotionPeriod extends PromotionSettingBase
{
    public const ID = 'promotionPeriod';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_DateInterval::get(__('Promotion duration', 'team-booking'), self::ID);
        $element->setDescription(__('The promotion will be valid within the specified date range.', 'team-booking'));

        return $element;
    }

    public static function default($resourceId): string
    {
        return '';
    }
}