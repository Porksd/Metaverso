<?php

namespace VSHM\Settings\Service;

use VSHM\Providers\Objects\Service;
use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class Color
 *
 * @package VSHM
 */
class Color extends ServiceSettingBase
{

    public const ID = 'color';

    private static $palette = [
        '#201923',
        '#ffffff',
        '#fcff5d',
        '#7dfc00',
        '#0ec434',
        '#228c68',
        '#8ad8e8',
        '#235b54',
        '#29bdab',
        '#3998f5',
        '#37294f',
        '#277da7',
        '#3750db',
        '#f22020',
        '#991919',
        '#ffcba5',
        '#e68f66',
        '#c56133',
        '#96341c',
        '#632819',
        '#ffc413',
        '#f47a22',
        '#2f2aa0',
        '#b732cc',
        '#772b9d',
        '#f07cab',
        '#d30b94',
        '#edeff3',
        '#c3a5b4',
        '#946aa2',
        '#5d4c86 ',
    ];

    public static function subscribe(): void
    {
        return;
    }

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Color::get(__('Color', 'team-booking'), self::ID);
        $element->setDescription(__('Select the color for the service.', 'team-booking'));

        return $element;
    }

    public static function getProcessedDefault(Service $service)
    {
        if ($service->color) {
            return $service->color;
        }

        return self::getDefault();
    }

    public static function getDefault(): string
    {
        return self::$palette[ array_rand(self::$palette) ];
    }
}