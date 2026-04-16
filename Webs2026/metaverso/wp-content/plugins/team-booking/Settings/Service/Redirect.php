<?php

namespace VSHM\Settings\Service;

use VSHM\UI\Admin\Alert;
use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class Redirect
 *
 * @package VSHM
 */
class Redirect extends ServiceSettingBase
{
    public const ID = 'redirect';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Toggle::get(__('Redirect', 'team-booking'), self::ID);
        $element->setDescription(__('If active, the customer will be redirected to the specified URL after a successful reservation for this service. This is meant for tracking conversions.', 'team-booking'));
        $element->setAlert(Alert::warning(__('About payments: when redirect is active, the immediate and later payment settings will work just fine, while discretion payment setting will not, so no payment option will be presented to the customer after the reservation in this case.', 'team-booking')));

        return $element;
    }

    public static function getDefault(): bool
    {
        return FALSE;
    }

    public static function whitelist($value, string $version)
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}