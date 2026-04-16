<?php

namespace VSHM\Settings\Service;

use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Toggle;

defined('ABSPATH') || exit;

/**
 * Class Personal_CancellationEmailToProvider
 *
 * @package VSHM
 */
class Personal_CancellationEmailToProvider extends ServicePersonalSettingBase
{
    public const ID = 'p_emailProviderCancellation';

    public const ID_SEND    = self::ID . '_Send';
    public const ID_SUBJECT = self::ID . '_Subject';
    public const ID_BODY    = self::ID . '_Body';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Notification::get(__('Get notified when a customer cancels a reservation for this service that was booked from you', 'team-booking'), self::ID);

        return $element;
    }

    public static function subscribe(): void
    {
        add_filter('vshm_default_service_personal_settings', static function ($defaults, $slug, $service) {
            if ($slug === vshm()->plugin['SLUG']) {
                $defaults[ static::ID_SEND ]    = filter_var($defaults[ static::ID_SEND ] ?? static::getDefault()[ static::ID_SEND ], FILTER_VALIDATE_BOOLEAN);
                $defaults[ static::ID_SUBJECT ] = $defaults[ static::ID_SUBJECT ] ?? static::getDefault()[ static::ID_SUBJECT ];
                $defaults[ static::ID_BODY ]    = $defaults[ static::ID_BODY ] ?? static::getDefault()[ static::ID_BODY ];
            }

            return $defaults;
        }, 10, 3);

        add_filter('vshm_ensure_service_personal_setting', static function ($value, $id, $service) {
            if (!in_array($id, [
                self::ID_SEND,
                self::ID_SUBJECT,
                self::ID_BODY,
            ], TRUE)) {
                return $value;
            }

            if (NULL === $value) {
                return static::getDefault()[ $id ];
            }

            return static::whitelist($value, vshm()->plugin['VERSION']);
        }, 10, 3);
    }

    public static function getDefault(): array
    {
        return [
            static::ID_SEND    => FALSE,
            static::ID_SUBJECT => __('Reservation cancelled', 'team-booking'),
            static::ID_BODY    => __('A reservation was cancelled', 'team-booking'),
        ];
    }
}