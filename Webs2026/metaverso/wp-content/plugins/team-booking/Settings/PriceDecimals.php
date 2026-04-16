<?php

namespace VSHM\Settings;

use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Option;

defined('ABSPATH') || exit;

/**
 * Class PriceDecimals
 */
class PriceDecimals extends SettingBase
{
    public const ID = 'priceDecimals';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Select::get(__('Price decimals', 'team-booking'), self::ID);
        $element->setDescription(__('The decimal separator here is taken as example, it does not necessarily reflect the actual separator.', 'team-booking'));
        $element->addOption(Settings_Option::get('100', 0));
        $element->addOption(Settings_Option::get('100.0', 1));
        $element->addOption(Settings_Option::get('100.00', 2));
        $element->addOption(Settings_Option::get('100.000', 3));
        $element->addOption(Settings_Option::get('100.0000', 4));

        return $element;
    }

    public static function sanitize($value): int
    {
        return ((int)$value >= 0 && (int)$value <= 4)
            ? (int)$value
            : static::default();
    }

    public static function default(): int
    {
        return 2;
    }
}