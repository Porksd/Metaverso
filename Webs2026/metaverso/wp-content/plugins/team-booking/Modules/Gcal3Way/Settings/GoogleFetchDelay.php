<?php

namespace VSHM\Modules\Gcal3Way\Settings;

use VSHM\UI\Admin\Alert;
use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class GoogleFetchDelay
 */
class GoogleFetchDelay extends GoogleSettingBase
{

    public const ID = 'googleFetchDelay';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Number::get(__('Google Calendar refresh delay', 'team-booking'), self::ID);
        $element->setDescription(__('If the value is greater than 0, the Google Calendar availability events will be cached for the specified time (in seconds)', 'team-booking'));
        $element->setAlert(Alert::info(__("If the Google Calendar availability is not changed too often, consider increasing this value to save Google API quota. However, note that events added to Google Calendar will only be detected by the plugin after this interval has passed.", 'team-booking')));

        return $element;
    }

    public static function sanitize($value): int
    {
        return (int)$value;
    }

    public static function default(): int
    {
        return 0;
    }
}