<?php

namespace VSHM\Settings\Service;

use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Option;
use VSHM\UI\Admin\Settings_Radios;

defined('ABSPATH') || exit;

/**
 * Class ConfirmationEmailToCustomer
 *
 * @package VSHM
 */
class ConfirmationEmailToCustomer extends ServiceSettingBase
{
    public const ID           = 'emailCustomerConfirmation';
    public const ID_SEND      = self::ID . '_Send';
    public const ID_SEND_FROM = self::ID . '_SendFrom';
    public const ID_SUBJECT   = self::ID . '_Subject';
    public const ID_BODY      = self::ID . '_Body';

    public const SEND_FROM_ADMIN    = 'admin';
    public const SEND_FROM_PROVIDER = 'coworker';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Notification::get(__('Send confirmation email', 'team-booking'), self::ID);
        $element->setDescription(__('An email notification will be sent to the customer after a booking is made.', 'team-booking'));

        $sender = Settings_Radios::get(__('Specify the email address that should appear as the sender.', 'team-booking'), self::ID_SEND_FROM);
        $option = Settings_Option::get(__('Administrator', 'team-booking'), self::SEND_FROM_ADMIN);
        $option->setDescription(__('The email address is taken from Settings > General > Sender email. The sender name will be the site title as set in WordPress > General.', 'team-booking'));
        $sender->addOption($option);
        $option = Settings_Option::get(__('Service Provider', 'team-booking'), self::SEND_FROM_PROVIDER);
        $option->setDescription(__('Email address and name as set in the WordPress user profile of the service provider', 'team-booking'));
        $sender->addOption($option);

        $element->addExtra($sender);

        return $element;
    }

    public static function subscribe(): void
    {
        add_filter('vshm_default_service_settings', static function ($defaults, $slug, $service) {
            if ($slug === vshm()->plugin['SLUG']) {
                $defaults[ static::ID_SEND ]      = filter_var($defaults[ static::ID_SEND ] ?? static::getDefault()[ static::ID_SEND ], FILTER_VALIDATE_BOOLEAN);
                $defaults[ static::ID_SEND_FROM ] = $defaults[ static::ID_SEND_FROM ] ?? static::getDefault()[ static::ID_SEND_FROM ];
                $defaults[ static::ID_SUBJECT ]   = $defaults[ static::ID_SUBJECT ] ?? static::getDefault()[ static::ID_SUBJECT ];
                $defaults[ static::ID_BODY ]      = $defaults[ static::ID_BODY ] ?? static::getDefault()[ static::ID_BODY ];
            }

            return $defaults;
        }, 10, 3);
    }

    public static function getDefault(): array
    {
        return [
            static::ID_SEND      => TRUE,
            static::ID_SEND_FROM => self::SEND_FROM_ADMIN,
            static::ID_SUBJECT   => __('Your reservation is confirmed', 'team-booking'),
            static::ID_BODY      => __('Thanks for your reservation!', 'team-booking'),
        ];
    }
}