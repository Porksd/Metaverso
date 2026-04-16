<?php

namespace VSHM\Settings\Service;

use VSHM\UI\Admin\Alert;
use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Dependency;

defined('ABSPATH') || exit;

/**
 * Class UntilApproval
 *
 * @package VSHM
 */
class UntilApproval extends ServiceSettingBase
{
    public const ID = 'untilApproval';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Toggle::get(__('Keep available until approval', 'team-booking'), self::ID);
        $element->setDescription(__('Activate this option if you want to keep the slot/tickets available for other reservations, until a reservation gets approved.', 'team-booking'));
        $element->setAlert(Alert::warning(__('Activating this option could lead to overbooking', 'team-booking')));

        $element->addDependency(Settings_Dependency::NOT_EQUAL(Approval::ID, Approval::NONE));

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