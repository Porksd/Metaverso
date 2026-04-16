<?php

namespace VSHM\Settings\Service;

use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Dependency;

defined('ABSPATH') || exit;

/**
 * Class CancellationReason
 *
 * @package VSHM
 */
class CancellationReason extends ServiceSettingBase
{
    public const ID = 'cancellationReason';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Toggle::get(__('Cancellation reason', 'team-booking'), self::ID);
        $element->setDescription(__('Allow customers to provide a reason when canceling a reservation.', 'team-booking'));

        $element->addDependency(Settings_Dependency::TRUTHY(AllowCancellation::ID));

        return $element;
    }

    public static function getDefault(): bool
    {
        return FALSE;
    }
}