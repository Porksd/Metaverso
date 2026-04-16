<?php

namespace VSHM\Settings\Promotion;

use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class PromotionType
 */
class PromotionType extends PromotionSettingBase
{
    public const ID       = 'promotionType';
    public const CAMPAIGN = 'campaign';
    public const COUPON = 'coupon';
    public const TYPES  = [
        self::CAMPAIGN,
        self::COUPON
    ];

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Radios::get(__('Promotion type', 'team-booking'), self::ID);
        $element->addOption(\VSHM\UI\Admin\Settings_Option::get(__('Campaign', 'team-booking'), self::CAMPAIGN));
        $element->addOption(\VSHM\UI\Admin\Settings_Option::get(__('Coupon', 'team-booking'), self::COUPON));

        return $element;
    }

    public static function default($resourceId): string
    {
        return self::CAMPAIGN;
    }
}