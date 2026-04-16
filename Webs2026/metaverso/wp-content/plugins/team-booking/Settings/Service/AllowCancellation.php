<?php

namespace VSHM\Settings\Service;

use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class AllowCancellation
 *
 * @package VSHM
 */
class AllowCancellation extends ServiceSettingBase
{
    public const ID = 'allowCancellation';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Toggle::get(__('Allow customers to cancel a reservation', 'team-booking'), self::ID);
        $element->setDescription(__('Customers will be able to cancel their reservations for this service either by using the cancellation link in the email (if provided) or by visiting the page where a reservation status widget is placed (in which case they must be logged in).', 'team-booking'));

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