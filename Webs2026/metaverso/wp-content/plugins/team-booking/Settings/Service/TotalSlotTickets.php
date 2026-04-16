<?php

namespace VSHM\Settings\Service;

use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Dependency;

defined('ABSPATH') || exit;

/**
 * Class TotalSlotTickets
 *
 * @package VSHM
 */
class TotalSlotTickets extends ServiceSettingBase
{

    public const ID = 'totalSlotTickets';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Number::get(__('Time slot capacity', 'team-booking'), self::ID);
        $element->setMin(1);
        $element->setStep(1);

        $element->addDependency(Settings_Dependency::NOT_EQUAL('class', 'unscheduled'));

        return $element;
    }

    public static function getDefault(): int
    {
        return 1;
    }

    public static function whitelist($value, string $version)
    {
        return (int)$value;
    }
}