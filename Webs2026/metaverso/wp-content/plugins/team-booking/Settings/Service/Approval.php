<?php

namespace VSHM\Settings\Service;

use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class Approval
 *
 * @package VSHM
 */
class Approval extends ServiceSettingBase
{
    public const ID = 'approval';

    public const NONE     = 'none';
    public const ADMIN    = 'admin';
    public const PROVIDER = 'coworker';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Radios::get(__('Approval requirement', 'team-booking'), self::ID);
        $element->setDescription(__('If enabled, no actions such as sending confirmation emails or updating Google Calendar will be performed until the reservation is approved.', 'team-booking'));
        $element->addOption(\VSHM\UI\Admin\Settings_Option::get(__("Don't require approval", 'team-booking'), static::NONE));
        $element->addOption(\VSHM\UI\Admin\Settings_Option::get(__('Require Admin approval', 'team-booking'), static::ADMIN));
        $element->addOption(\VSHM\UI\Admin\Settings_Option::get(__('Require Provider approval', 'team-booking'), static::PROVIDER));

        return $element;
    }

    public static function getDefault(): string
    {
        return static::NONE;
    }
}