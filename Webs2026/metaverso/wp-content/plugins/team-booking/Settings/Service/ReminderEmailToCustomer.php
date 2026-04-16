<?php

namespace VSHM\Settings\Service;

use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Dependency;
use VSHM\UI\Admin\Settings_Option;
use VSHM\UI\Admin\Settings_Radios;
use VSHM\UI\Admin\Settings_Select;

defined('ABSPATH') || exit;

/**
 * Class ReminderEmailToCustomer
 *
 * @package VSHM
 */
class ReminderEmailToCustomer extends ServiceSettingBase
{
    public const ID = 'emailCustomerReminder';

    public const ID_SEND        = self::ID . '_Send';
    public const ID_SEND_FROM   = self::ID . '_SendFrom';
    public const ID_SUBJECT     = self::ID . '_Subject';
    public const ID_BODY        = self::ID . '_Body';
    public const ID_DAYS_BEFORE = self::ID . '_DaysBefore';

    public const SEND_FROM_ADMIN    = 'admin';
    public const SEND_FROM_PROVIDER = 'coworker';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Notification::get(__('Send reminder email', 'team-booking'), self::ID);
        $element->setDescription(__('Reminders are sent using the WordPress Cron system, which relies on site visits to trigger the scheduled events. If your site has low traffic, there may be delays or the reminders may not be sent at all.', 'team-booking'));

        $days = Settings_Select::get('', self::ID_DAYS_BEFORE);
        for ($i = 1; $i <= 5; $i++) {
            $days->addOption(Settings_Option::get(sprintf(
            /* translators: %d: number of days */
                _n('%d day before', '%d days before', $i, 'team-booking'),
                $i
            ), $i));
        }
        $element->addExtra($days);

        $sender = Settings_Radios::get(__('Specify the email address that should appear as the sender.', 'team-booking'), self::ID_SEND_FROM);
        $option = Settings_Option::get(__('Administrator', 'team-booking'), self::SEND_FROM_ADMIN);
        $option->setDescription(__('The email address is taken from Settings > General > Sender email. The sender name will be the site title as set in WordPress > General.', 'team-booking'));
        $sender->addOption($option);
        $option = Settings_Option::get(__('Service Provider', 'team-booking'), self::SEND_FROM_PROVIDER);
        $option->setDescription(__('Email address and name as set in the WordPress user profile of the service provider', 'team-booking'));
        $sender->addOption($option);

        $element->addExtra($sender);

        $element->addDependency(Settings_Dependency::NOT_EQUAL('class', 'unscheduled'));

        return $element;
    }

    public static function subscribe(): void
    {
        add_filter('vshm_default_service_settings', static function ($defaults, $slug, $service) {
            if ($slug !== vshm()->plugin['SLUG']) {
                return $defaults;
            }
            $defaults[ static::ID_SEND ]        = filter_var($defaults[ static::ID_SEND ] ?? static::getDefault()[ static::ID_SEND ], FILTER_VALIDATE_BOOLEAN);
            $defaults[ static::ID_SEND_FROM ]   = $defaults[ static::ID_SEND_FROM ] ?? static::getDefault()[ static::ID_SEND_FROM ];
            $defaults[ static::ID_SUBJECT ]     = $defaults[ static::ID_SUBJECT ] ?? static::getDefault()[ static::ID_SUBJECT ];
            $defaults[ static::ID_BODY ]        = $defaults[ static::ID_BODY ] ?? static::getDefault()[ static::ID_BODY ];
            $defaults[ static::ID_DAYS_BEFORE ] = (int)($defaults[ static::ID_DAYS_BEFORE ] ?? static::getDefault()[ static::ID_DAYS_BEFORE ]);

            return $defaults;
        }, 10, 3);

        add_filter('tbk_service_data_value', static function ($value, $key, $serviceId) {
            if ($key === static::ID_DAYS_BEFORE) {
                return (int)$value;
            }

            return $value;
        }, 10, 3);
    }

    public static function getDefault(): array
    {
        return [
            static::ID_SEND        => FALSE,
            static::ID_SEND_FROM   => self::SEND_FROM_ADMIN,
            static::ID_SUBJECT     => __("Don't forget your reservation", 'team-booking'),
            static::ID_BODY        => __('We are getting close!', 'team-booking'),
            static::ID_DAYS_BEFORE => 2,
        ];
    }
}