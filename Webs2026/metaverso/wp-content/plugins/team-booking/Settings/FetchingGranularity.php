<?php

namespace VSHM\Settings;

use VSHM\UI\Admin\Alert;
use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class FetchingGranularity
 */
class FetchingGranularity extends SettingBase
{

    public const ID = 'fetchGranularity';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Number::get(__('Fetch granularity', 'team-booking'), self::ID);
        $element->setMin(1);
        $element->setMax(12);
        $element->setStep(1);
        $element->setDescription(__('Number of months to query for events in a single API call.', 'team-booking'));
        $element->setAlert(Alert::info(__('Default is 1. Try to raise it if you have sparse events and you want to speed up the loading times, or if you are hitting the limits of Google API usage quota too often.', 'team-booking')));

        return $element;
    }

    public static function sanitize($value): int
    {
        return (int)$value ?: 1;
    }

    public static function default(): int
    {
        return 1;
    }
}