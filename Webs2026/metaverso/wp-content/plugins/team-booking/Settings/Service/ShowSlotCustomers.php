<?php

namespace VSHM\Settings\Service;

use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Dependency;

defined('ABSPATH') || exit;

/**
 * Class ShowSlotCustomers
 *
 * @package VSHM
 */
class ShowSlotCustomers extends ServiceSettingBase
{
    public const ID = 'showSlotCustomers';

    public const NO         = 'no';
    public const NAME       = 'name';
    public const EMAIL      = 'email';
    public const NAME_EMAIL = 'name_email';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Radios::get(__('Show slot customers', 'team-booking'), self::ID);
        $element->setDescription(__('Choose whether to display a list of the attendees or not, and which data it should include', 'team-booking'));
        $element->addOption(\VSHM\UI\Admin\Settings_Option::get(__("Don't show", 'team-booking'), self::NO));
        $element->addOption(\VSHM\UI\Admin\Settings_Option::get(__('Show names', 'team-booking'), self::NAME));
        $element->addOption(\VSHM\UI\Admin\Settings_Option::get(__('Show email', 'team-booking'), self::EMAIL));
        $element->addOption(\VSHM\UI\Admin\Settings_Option::get(__('Show names and email', 'team-booking'), self::NAME_EMAIL));
        $element->setAlert(\VSHM\UI\Admin\Alert::warning(__('Please carefully consider any privacy-related implications', 'team-booking')));
        $element->addDependency(Settings_Dependency::NOT_EQUAL('class', 'unscheduled'));
        $element->addDependency(Settings_Dependency::TRUTHY(ShowBookedSlots::ID));

        return $element;
    }

    public static function getDefault()
    {
        return self::NO;
    }
}