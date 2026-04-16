<?php

namespace VSHM\Settings;

use VSHM\UI\Admin\Alert;
use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class PrepopulateBookingForm
 */
class PrepopulateBookingForm extends SettingBase
{

    public const ID = 'prepopulateBookingForm';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Toggle::get(__('Pre-populate booking form', 'team-booking'), self::ID);
        $element->setDescription(__('Controls if the booking form fields should be pre-populated with available data for logged-in customers.', 'team-booking'));
        $element->setAlert(Alert::info(__('Each form field should be instructed where to fetch data through a proper meta-key configuration.', 'team-booking')));

        return $element;
    }

    public static function sanitize($value)
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public static function default(): bool
    {
        return FALSE;
    }
}