<?php

namespace VSHM\Settings\Promotion;

use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class DiscountType
 */
class DiscountType extends PromotionSettingBase
{
    public const ID = 'discountType';

    public const DIRECT     = 'direct';
    public const PERCENTAGE = 'percentage';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Radios::get(__('Discount type', 'team-booking'), self::ID);
        $element->addOption(\VSHM\UI\Admin\Settings_Option::get(__('Percentage (%)', 'team-booking'), self::PERCENTAGE));
        $element->addOption(\VSHM\UI\Admin\Settings_Option::get(__('Fixed amount', 'team-booking'), self::DIRECT));

        return $element;
    }

    public static function default($resourceId): string
    {
        return self::PERCENTAGE;
    }
}