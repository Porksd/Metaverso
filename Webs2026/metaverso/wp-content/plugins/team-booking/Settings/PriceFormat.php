<?php

namespace VSHM\Settings;

use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Option;

defined('ABSPATH') || exit;

/**
 * Class PriceFormat
 */
class PriceFormat extends SettingBase
{
    public const ID          = 'priceFormat';
    public const DOT_COMMA   = '.,';
    public const SPACE_COMMA = '_,';
    public const COMMA_DOT   = ',.';
    public const SPACE_DOT   = '_.';
    public const QUOTE_DOT   = "'.";

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Select::get(__('Price format', 'team-booking'), self::ID);
        $element->setDescription(__('The number of decimals here are taken as example, they does not necessarily reflect the actual number of decimals.', 'team-booking'));
        $element->addOption(Settings_Option::get('1.000,00', self::DOT_COMMA));
        $element->addOption(Settings_Option::get('1 000,00', self::SPACE_COMMA));
        $element->addOption(Settings_Option::get('1,000.00', self::COMMA_DOT));
        $element->addOption(Settings_Option::get('1 000.00', self::SPACE_DOT));
        $element->addOption(Settings_Option::get("1'000.00", self::QUOTE_DOT));

        return $element;
    }

    public static function sanitize($value): string
    {
        return in_array((string)$value, [
            self::DOT_COMMA,
            self::SPACE_COMMA,
            self::COMMA_DOT,
            self::SPACE_DOT,
            self::QUOTE_DOT,
        ])
            ? (string)$value
            : static::default();
    }

    public static function default(): string
    {
        return self::DOT_COMMA;
    }
}