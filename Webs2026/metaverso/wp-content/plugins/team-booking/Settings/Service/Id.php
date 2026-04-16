<?php

namespace VSHM\Settings\Service;

use VSHM\Providers\Objects\Service;
use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class Id
 *
 * @package VSHM
 */
class Id extends ServiceSettingBase
{

    public const ID = 'id';

    public static function subscribe(): void
    {
        return;
    }

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Input::get(__('Id', 'team-booking'), self::ID);
        $element->isReadOnly(TRUE);

        return $element;
    }

    public static function getProcessedDefault(Service $service)
    {
        if ($service->id) {
            return $service->id;
        }

        return self::getDefault();
    }

    public static function getDefault(): string
    {
        return '';
    }
}