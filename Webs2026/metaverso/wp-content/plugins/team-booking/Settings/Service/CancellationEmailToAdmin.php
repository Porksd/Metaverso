<?php

namespace VSHM\Settings\Service;

use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_SelectWpUserEmail;

defined('ABSPATH') || exit;

/**
 * Class CancellationEmailToAdmin
 *
 * @package VSHM
 */
class CancellationEmailToAdmin extends ServiceSettingBase
{
    public const ID = 'emailAdminCancellation';

    public const ID_SEND    = self::ID . '_Send';
    public const ID_SEND_TO = self::ID . '_SendTo';
    public const ID_SUBJECT = self::ID . '_Subject';
    public const ID_BODY    = self::ID . '_Body';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Notification::get(__('Send cancellation email', 'team-booking'), self::ID);
        $element->setDescription(__('An email notification is sent to the admin after a booking is cancelled', 'team-booking'));

        $recipient = Settings_SelectWpUserEmail::get(__('Recipients administrators', 'team-booking'), self::ID_SEND_TO);
        $recipient->setRole('admin');

        $element->addExtra($recipient);

        return $element;
    }

    public static function subscribe(): void
    {
        add_filter('vshm_default_service_settings', static function ($defaults, $slug, $service) {
            if ($slug === vshm()->plugin['SLUG']) {
                $defaults[ static::ID_SEND ]    = filter_var($defaults[ static::ID_SEND ] ?? static::getDefault()[ static::ID_SEND ], FILTER_VALIDATE_BOOLEAN);
                $defaults[ static::ID_SEND_TO ] = $defaults[ static::ID_SEND_TO ] ?? static::getDefault()[ static::ID_SEND_TO ];
                $defaults[ static::ID_SUBJECT ] = $defaults[ static::ID_SUBJECT ] ?? static::getDefault()[ static::ID_SUBJECT ];
                $defaults[ static::ID_BODY ]    = $defaults[ static::ID_BODY ] ?? static::getDefault()[ static::ID_BODY ];
            }

            return $defaults;
        }, 10, 3);
    }

    public static function getDefault(): array
    {
        return [
            static::ID_SEND    => TRUE,
            static::ID_SEND_TO => get_option('admin_email'),
            static::ID_SUBJECT => __('Reservation cancelled', 'team-booking'),
            static::ID_BODY    => __('A reservation was cancelled.', 'team-booking'),
        ];
    }
}