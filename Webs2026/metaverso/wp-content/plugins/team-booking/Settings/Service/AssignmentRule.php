<?php

namespace VSHM\Settings\Service;

use VSHM\UI\Admin\Alert;
use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Dependency;

defined('ABSPATH') || exit;

/**
 * Class AssignmentRule
 *
 * @package VSHM
 */
class AssignmentRule extends ServiceSettingBase
{
    public const ID = 'assignmentRule';

    public const EQUAL  = 'equal';
    public const DIRECT = 'direct';
    public const RANDOM = 'random';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Radios::get(__('Service provider assignment rule', 'team-booking'), self::ID);
        $element->setDescription(__('In case of multiple service providers, you can specify the assignment rule that the plugin should follow after each reservation.', 'team-booking'));
        $option = \VSHM\UI\Admin\Settings_Option::get(__('Equal', 'team-booking'), self::EQUAL);
        $option->setDescription(__('A new reservation will be assigned to the service provider with the fewest number of reservations already assigned to them, based on the reservations stored in the database.', 'team-booking'));
        $element->addOption($option);
        $option = \VSHM\UI\Admin\Settings_Option::get(__('Direct', 'team-booking'), self::DIRECT);
        $option->setDescription(__('Always assign the reservation to a specific service provider.', 'team-booking'));
        $element->addOption($option);
        $option = \VSHM\UI\Admin\Settings_Option::get(__('Random', 'team-booking'), self::RANDOM);
        $option->setDescription(__('A random provider will be selected for the assignment.', 'team-booking'));
        $element->addOption($option);

        $element->addDependency(Settings_Dependency::EQUAL('class', 'unscheduled'));

        return $element;
    }

    public static function getDefault(): string
    {
        return self::EQUAL;
    }
}