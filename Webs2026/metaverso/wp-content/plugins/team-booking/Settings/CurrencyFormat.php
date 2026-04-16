<?php

namespace VSHM\Settings;

use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Option;

defined('ABSPATH') || exit;

/**
 * Class CurrencyFormat
 */
class CurrencyFormat extends SettingBase
{
    public const ID                    = 'currencyFormat';
    public const CURRENCY_BEFORE_SPACE = '! #';
    public const CURRENCY_BEFORE       = '!#';
    public const CURRENCY_AFTER_SPACE  = '# !';
    public const CURRENCY_AFTER        = '#!';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Select::get(__('Currency format', 'team-booking'), self::ID);
        $element->setDescription(__('The $ symbol here is taken as example, it does not necessarily reflect the actual currency.', 'team-booking'));
        $element->addOption(Settings_Option::get('$ 100', self::CURRENCY_BEFORE_SPACE));
        $element->addOption(Settings_Option::get('$100', self::CURRENCY_BEFORE));
        $element->addOption(Settings_Option::get('100 $', self::CURRENCY_AFTER_SPACE));
        $element->addOption(Settings_Option::get('100$', self::CURRENCY_AFTER));

        return $element;
    }

    public static function sanitize($value): string
    {
        return in_array((string)$value, [
            self::CURRENCY_BEFORE_SPACE,
            self::CURRENCY_BEFORE,
            self::CURRENCY_AFTER_SPACE,
            self::CURRENCY_AFTER,
        ])
            ? (string)$value
            : static::default();
    }

    public static function default(): string
    {
        return self::CURRENCY_BEFORE_SPACE;
    }
}