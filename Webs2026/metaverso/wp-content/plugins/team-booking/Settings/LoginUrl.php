<?php

namespace VSHM\Settings;

use VSHM\UI\Admin\Alert;
use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class LoginUrl
 */
class LoginUrl extends SettingBase
{

    public const ID = 'loginUrl';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Input::get(__('Login URL', 'team-booking'), self::ID);
        $element->setDescription(__('When attempting to book a service for logged-in users only, customers will be prompted to log in at this page.', 'team-booking'));
        $element->setPlaceholder(wp_login_url());
        $element->setAlert(Alert::info(__('Leave it empty to use the WordPress default behaviour.', 'team-booking')));

        return $element;
    }

    public static function sanitize($value): ?string
    {
        return filter_var($value, FILTER_VALIDATE_URL) ? $value : static::default();
    }

    public static function default(): ?string
    {
        return wp_login_url();
    }
}