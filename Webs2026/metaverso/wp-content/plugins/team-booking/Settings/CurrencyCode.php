<?php

namespace VSHM\Settings;

use VSHM\Tools;
use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Option;

defined('ABSPATH') || exit;

/**
 * Class CurrencyCode
 */
class CurrencyCode extends SettingBase
{
    public const ID = 'currencyCode';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Select::get(__('Currency', 'team-booking'), self::ID);
        foreach (Tools::getCurrencies() as $code => $currency) {
            $element->addOption(Settings_Option::get($code . ' - ' . $currency['label'] . ' (' . html_entity_decode($currency['symbol']) . ')', $code));
        }

        return $element;
    }

    public static function sanitize($value): string
    {
        return array_key_exists((string)$value, Tools::getCurrencies()) ? (string)$value : static::default();
    }

    public static function default(): string
    {
        return 'USD';
    }
}