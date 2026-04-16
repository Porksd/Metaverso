<?php

namespace VSHM\Settings\Service;

use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class ShowProvider
 *
 * @package VSHM
 */
class ShowProvider extends ServiceSettingBase
{
    public const ID = 'showProvider';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Toggle::get(__('Show provider name', 'team-booking'), self::ID);

        return $element;
    }

    public static function getDefault(): bool
    {
        return TRUE;
    }

    public static function whitelist($value, string $version)
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}