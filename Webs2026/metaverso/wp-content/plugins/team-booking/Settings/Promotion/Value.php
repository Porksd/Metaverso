<?php

namespace VSHM\Settings\Promotion;

use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class Value
 */
class Value extends PromotionSettingBase
{

    public const ID = 'promotionValue';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Number::get(__('Discount value', 'team-booking'), self::ID);
        $element->setDescription(__("The discount will be applied to the base price of the service.", 'team-booking'));
        $element->setMin(0);
        $element->setStep(1);

        return $element;
    }

    public static function default($resourceId): int
    {
        return 10;
    }
}