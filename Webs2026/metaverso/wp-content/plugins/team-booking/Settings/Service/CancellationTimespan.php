<?php

namespace VSHM\Settings\Service;

use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Dependency;

defined('ABSPATH') || exit;

/**
 * Class CancellationTimespan
 *
 * @package VSHM
 */
class CancellationTimespan extends ServiceSettingBase
{
    public const ID = 'cancellationTimespan';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_DHM::get(__('Cancellation timespan', 'team-booking'), self::ID);
        $element->setDescription(__('Customers are not able to cancel their reservations when the start date and time are within this specified timespan.', 'team-booking'));

        $element->addDependency(Settings_Dependency::TRUTHY(AllowCancellation::ID));
        $element->addDependency(Settings_Dependency::NOT_EQUAL('class', 'unscheduled'));

        return $element;
    }

    public static function getDefault(): int
    {
        return 1 * DAY_IN_SECONDS;
    }
}