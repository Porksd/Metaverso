<?php

namespace VSHM\Settings\Service;

use VSHM\UI\Admin\Alert;
use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Dependency;

defined('ABSPATH') || exit;

/**
 * Class TotalUserSlotTickets
 *
 * @package VSHM
 */
class TotalUserSlotTickets extends ServiceSettingBase
{

    public const ID = 'totalUserSlotTickets';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Number::get(__('Max user tickets per slot', 'team-booking'), self::ID);
        $element->setDescription(__('The maximum number of tickets that a customer is allowed to book for a single slot. 0 means up to the maximum available tickets.', 'team-booking'));
        $element->setMin(0);
        $element->setStep(1);

        $element->addDependency(Settings_Dependency::NOT_EQUAL('class', 'unscheduled'));
        $element->addDependency(Settings_Dependency::NOT_EQUAL(TotalSlotTickets::ID, 1));

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