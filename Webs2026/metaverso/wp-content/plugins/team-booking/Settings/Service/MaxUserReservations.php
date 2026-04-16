<?php

namespace VSHM\Settings\Service;

use VSHM\UI\Admin\Alert;
use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Dependency;

defined('ABSPATH') || exit;

/**
 * Class MaxUserReservations
 *
 * @package VSHM
 */
class MaxUserReservations extends ServiceSettingBase
{

    public const ID = 'maxUserReservations';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Number::get(__('Max reservations per user', 'team-booking'), self::ID);
        $element->setDescription(__("The book button will display the number of reservations remaining for the current user. If the limit is reached, the user will not be able to proceed. Set 0 for no limit.", 'team-booking'));
        $element->setMin(0);
        $element->setStep(1);

        $element->addDependency(Settings_Dependency::EQUAL('class', 'unscheduled'));
        $element->addDependency(Settings_Dependency::EQUAL(Access::ID, Access::LOGGED_ONLY));

        return $element;
    }

    public static function getDefault(): int
    {
        return 0;
    }
}