<?php

namespace VSHM\Settings\Promotion;

use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class Name
 */
class Name extends PromotionSettingBase
{

    public const ID = 'promotionName';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Input::get(__('Name', 'team-booking'), self::ID);
        $element->setDescription(__('Please enter the name of the promotion.', 'team-booking'));

        return $element;
    }

    public static function default($resourceId): string
    {
        return '';
    }
}