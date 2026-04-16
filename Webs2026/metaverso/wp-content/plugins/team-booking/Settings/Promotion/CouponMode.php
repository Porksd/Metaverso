<?php

namespace VSHM\Settings\Promotion;

use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Dependency;

defined('ABSPATH') || exit;

/**
 * Class CouponMode
 */
class CouponMode extends PromotionSettingBase
{
    public const ID    = 'couponMode';

    public const LIST  = 'list';
    public const FIXED = 'fixed';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Radios::get(__('Coupon configuration', 'team-booking'), self::ID);
        $element->setDescription(__('If set to "fixed", the coupon text will be the same as the promotion name. Alternatively, you can enter a list of single-use coupons, separated by commas. Please refer to the documentation for more information on coupon configuration.', 'team-booking'));
        $element->addOption(\VSHM\UI\Admin\Settings_Option::get(__('Fixed', 'team-booking'), self::FIXED));
        $element->addOption(\VSHM\UI\Admin\Settings_Option::get(__('List', 'team-booking'), self::LIST));

        $element->addDependency(Settings_Dependency::EQUAL(PromotionType::ID, PromotionType::COUPON));

        return $element;
    }

    public static function default($resourceId): string
    {
        return self::FIXED;
    }
}