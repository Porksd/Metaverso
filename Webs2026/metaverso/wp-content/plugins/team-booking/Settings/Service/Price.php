<?php

namespace VSHM\Settings\Service;

use VSHM\Settings\CurrencyCode;
use VSHM\Settings\PriceDecimals;
use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class Price
 */
class Price extends ServiceSettingBase
{

    public const ID = 'price';

    public static function getBackendElement(): Element_Setting
    {
        switch (vshm()->settings->get(PriceDecimals::ID )) {
            case 1:
                $step = 0.1;
                break;
            case 3:
                $step = 0.001;
                break;
            case 4:
                $step = 0.0001;
                break;
            case 2:
            default:
                $step = 0.01;
                break;
        }

        $element = \VSHM\UI\Admin\Settings_Number::get(__('Price', 'team-booking'), self::ID);
        $element->setDescription(__("If zero, won't appear. To change the currency, navigate to the Settings section.", 'team-booking'));
        $element->setMin(0);
        $element->setStep($step);
        // TODO
        $element->setPrefix(vshm()->settings->get(CurrencyCode::ID));

        return $element;
    }

    public static function getDefault()
    {
        return 0;
    }
}