<?php

namespace VSHM\Settings;

use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class SortingProviders
 */
class SortingProviders extends SettingBase
{

    public const ID = 'sortingProviders';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_ProvidersOrder::get(__('Providers sorting order', 'team-booking'), self::ID);
        $element->setDescription(__('This determines the sorting order of service providers during the selection step.', 'team-booking'));

        return $element;
    }

    public static function default(): string
    {
        return '';
    }
}