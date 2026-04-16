<?php

namespace VSHM\Settings;

use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class SortingServices
 */
class SortingServices extends SettingBase
{

    public const ID = 'sortingServices';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_ServicesOrder::get(__('Services sorting order', 'team-booking'), self::ID);
        $element->setDescription(__('This determines the sorting order of services during the selection step.', 'team-booking'));

        return $element;
    }

    public static function default(): string
    {
        return '';
    }
}