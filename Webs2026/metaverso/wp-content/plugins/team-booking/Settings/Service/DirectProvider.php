<?php

namespace VSHM\Settings\Service;

use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Dependency;

defined('ABSPATH') || exit;

/**
 * Class DirectProvider
 *
 * @package VSHM
 */
class DirectProvider extends ServiceSettingBase
{
    public const ID = 'directProvider';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_SelectProvider::get(__('Direct provider for assignment', 'team-booking'), self::ID);

        $element->addDependency(Settings_Dependency::EQUAL('class', 'unscheduled'));
        $element->addDependency(Settings_Dependency::EQUAL(AssignmentRule::ID, AssignmentRule::DIRECT));

        return $element;
    }

    public static function getDefault(): int
    {
        return 0;
    }
}