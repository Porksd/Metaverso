<?php

namespace VSHM\Settings\Service;

use VSHM\Providers\Objects\Service;
use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class Name
 *
 * @package VSHM
 */
class Name extends ServiceSettingBase
{

    public const ID = 'name';

    public static function subscribe(): void
    {
        return;
    }

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Input::get(__('Name', 'team-booking'), self::ID);
        $element->setDescription(__('Choose the name of the service', 'team-booking'));

        return $element;
    }

    public static function getProcessedDefault(Service $service)
    {
        return $service->name ?? self::getDefault();
    }

    public static function getDefault(): string
    {
        return '';
    }
}