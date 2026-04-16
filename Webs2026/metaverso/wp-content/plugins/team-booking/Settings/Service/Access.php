<?php

namespace VSHM\Settings\Service;

use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class Access
 *
 * @package VSHM
 */
class Access extends ServiceSettingBase
{
    public const ID = 'access';

    public const EVERYONE    = 'everyone';
    public const LOGGED_ONLY = 'logged_only';
    public const NOBODY      = 'nobody';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Radios::get(__('Who can make a reservation', 'team-booking'), self::ID);
        $element->setDescription(__('If "Logged user only" is selected, a notice with a link to the registration page will be displayed. The default link can be changed in the "Settings" section.', 'team-booking'));
        $element->addOption(\VSHM\UI\Admin\Settings_Option::get(__('Everyone', 'team-booking'), self::EVERYONE));
        $element->addOption(\VSHM\UI\Admin\Settings_Option::get(__('Logged users only', 'team-booking'), self::LOGGED_ONLY));
        $element->addOption(\VSHM\UI\Admin\Settings_Option::get(__('Nobody (read-only)', 'team-booking'), self::NOBODY));

        return $element;
    }

    public static function getDefault(): string
    {
        return self::EVERYONE;
    }
}