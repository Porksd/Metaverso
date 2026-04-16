<?php

namespace VSHM\Settings;

use VSHM\UI\Admin\Alert;
use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class SenderEmail
 */
class SenderEmail extends SettingBase
{
    public const ID = 'senderEmail';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Input::get(__('Sender email', 'team-booking'), self::ID);
        $element->setDescription(__('Please provide the email address that will be used as the sender of notifications from the system. If left empty or invalid, the admin email address of the WordPress website will be used instead.', 'team-booking'));

        $element->setAlert(Alert::warning(__("Make sure that the email address you provide belongs to the domain of your mail server. Emails sent from unrecognized domains may be blocked by the recipient's email service.", 'team-booking')));

        return $element;
    }

    public static function sanitize($value): string
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) ? trim($value) : '';
    }

    public static function default(): string
    {
        return '';
    }
}