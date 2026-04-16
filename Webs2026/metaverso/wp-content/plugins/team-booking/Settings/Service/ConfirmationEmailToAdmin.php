<?php

namespace VSHM\Settings\Service;

use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_SelectWpUserEmail;
use VSHM\UI\Admin\Settings_Toggle;

defined('ABSPATH') || exit;

/**
 * Class ConfirmationEmailToAdmin
 *
 * @package VSHM
 */
class ConfirmationEmailToAdmin extends ServiceSettingBase
{
    public const        ID = 'emailAdminConfirmation';

    public const ID_SEND        = self::ID . '_Send';
    public const ID_SEND_TO     = self::ID . '_SendTo';
    public const ID_SUBJECT     = self::ID . '_Subject';
    public const ID_BODY        = self::ID . '_Body';
    public const ID_ATTACHMENTS = self::ID . '_SendAttachments';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Notification::get(__('Send confirmation email', 'team-booking'), self::ID);
        $element->setDescription(__('An email notification will be sent to the admin after a booking is made.', 'team-booking'));

        $attachments = Settings_Toggle::get(__('Include uploaded files as attachments', 'team-booking'), self::ID_ATTACHMENTS);
        $attachments->setDescription(__('If the reservation form collects one or more file from the customer, you can enable or disable the inclusion of those files as email attachments.', 'team-booking'));

        $element->addExtra($attachments);

        $recipient = Settings_SelectWpUserEmail::get(__('Recipients administrators', 'team-booking'), self::ID_SEND_TO);
        $recipient->setRole('admin');

        $element->addExtra($recipient);

        return $element;
    }

    public static function subscribe(): void
    {
        add_filter('vshm_default_service_settings', static function ($defaults, $slug, $service) {
            if ($slug === vshm()->plugin['SLUG']) {
                $defaults[ static::ID_SEND ]        = filter_var($defaults[ static::ID_SEND ] ?? static::getDefault()[ static::ID_SEND ], FILTER_VALIDATE_BOOLEAN);
                $defaults[ static::ID_ATTACHMENTS ] = filter_var($defaults[ static::ID_ATTACHMENTS ] ?? static::getDefault()[ static::ID_ATTACHMENTS ], FILTER_VALIDATE_BOOLEAN);
                $defaults[ static::ID_SEND_TO ]     = $defaults[ static::ID_SEND_TO ] ?? static::getDefault()[ static::ID_SEND_TO ];
                $defaults[ static::ID_SUBJECT ]     = $defaults[ static::ID_SUBJECT ] ?? static::getDefault()[ static::ID_SUBJECT ];
                $defaults[ static::ID_BODY ]        = $defaults[ static::ID_BODY ] ?? static::getDefault()[ static::ID_BODY ];
            }

            return $defaults;
        }, 10, 3);
    }

    public static function getDefault(): array
    {
        return [
            static::ID_SEND        => TRUE,
            static::ID_ATTACHMENTS => FALSE,
            static::ID_SEND_TO     => get_option('admin_email'),
            static::ID_SUBJECT     => __('A new reservation', 'team-booking'),
            static::ID_BODY        => __('You just got a new reservation!', 'team-booking'),
        ];
    }
}