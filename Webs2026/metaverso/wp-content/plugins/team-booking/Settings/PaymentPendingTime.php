<?php

namespace VSHM\Settings;

use VSHM\UI\Admin\Alert;
use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Option;

defined('ABSPATH') || exit;

/**
 * Class PaymentPendingTime
 */
class PaymentPendingTime extends SettingBase
{
    public const ID = 'paymentPendingTime';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Select::get(__('Pending payment expiration time', 'team-booking'), self::ID);
        $element->setDescription(__('The customer must complete the online payment within this specified time frame.', 'team-booking'));
        $minutes = [5, 10, 15, 30, 45];
        $hours   = [1, 2, 3, 4, 5, 6, 12, 24];
        foreach ($minutes as $minute) {
            $element->addOption(Settings_Option::get(sprintf(
            /* translators: %d: number of minutes */
                _n('%d minute', '%d minutes', $minute, 'team-booking'),
                $minute
            ), $minute * MINUTE_IN_SECONDS));
        }
        foreach ($hours as $hour) {
            $element->addOption(Settings_Option::get(sprintf(
            /* translators: %d: number of hours */
                _n('%d hour', '%d hours', $hour, 'team-booking'),
                $hour
            ), $hour * HOUR_IN_SECONDS));
        }
        $element->setAlert(Alert::warning(__('It is not recommended to set a very short time as there could be delays in the acknowledgment of the payment by your web server for various reasons.', 'team-booking')));

        return $element;
    }

    public static function sanitize($value): int
    {
        return (int)$value >= 0 ? (int)$value : static::default();
    }

    public static function default(): int
    {
        return HOUR_IN_SECONDS;
    }
}