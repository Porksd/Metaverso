<?php

namespace VSHM\Settings\Service;

use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class PaymentRequirement
 *
 * @package VSHM
 */
class PaymentRequirement extends ServiceSettingBase
{
    public const ID = 'paymentRequirement';

    public const IMMEDIATE     = 'immediately';
    public const LATER         = 'later';
    public const DISCRETIONARY = 'discretional';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Radios::get(__('How payment is required', 'team-booking'), self::ID);

        $option = \VSHM\UI\Admin\Settings_Option::get(__('Online', 'team-booking'), self::IMMEDIATE);
        $option->setDescription(__('Customers are required to make an online payment. If no payment is made within the specified pending time, the reservation will be automatically cancelled.', 'team-booking'));
        $element->addOption($option);
        $option = \VSHM\UI\Admin\Settings_Option::get(__('Local', 'team-booking'), self::LATER);
        $option->setDescription(__('The plugin will not handle the payment. You should manually set the reservation as "Paid" when the payment is done locally.', 'team-booking'));
        $element->addOption($option);
        $option = \VSHM\UI\Admin\Settings_Option::get(__('Discretionary', 'team-booking'), self::DISCRETIONARY);
        $option->setDescription(__('Customers can choose whether to pay online or pay later/locally.', 'team-booking'));
        $element->addOption($option);

        return $element;
    }

    public static function getDefault(): string
    {
        return self::IMMEDIATE;
    }
}