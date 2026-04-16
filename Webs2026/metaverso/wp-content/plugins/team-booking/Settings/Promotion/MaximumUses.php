<?php

namespace VSHM\Settings\Promotion;

use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Dependency;

defined('ABSPATH') || exit;

/**
 * Class MaximumUses
 */
class MaximumUses extends PromotionSettingBase
{

    public const ID = 'maxUses';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Number::get(__('Global discount usage limit', 'team-booking'), self::ID);
        $element->setDescription(__("Specify the maximum number of times the discount can be used globally across all reservations. Enter 0 for no limit.", 'team-booking'));
        $element->setMin(0);
        $element->setStep(1);
        $element->addDependency(Settings_Dependency::EQUAL(CouponMode::ID, CouponMode::FIXED));

        return $element;
    }

    public static function default($resourceId): int
    {
        return 0;
    }
}