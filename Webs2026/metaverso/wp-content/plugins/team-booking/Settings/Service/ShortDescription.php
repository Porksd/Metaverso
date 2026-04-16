<?php

namespace VSHM\Settings\Service;

use VSHM\Providers\Objects\Service;
use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class ShortDescription
 *
 * @package VSHM
 */
class ShortDescription extends ServiceSettingBase
{
    public const ID = 'shortDescription';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Input::get(__('Short description', 'team-booking'), self::ID);
        $element->setDescription(__('Short text to describe the service', 'team-booking'));

        return $element;
    }

    public static function getProcessedDefault(Service $service)
    {
        return $service->data['shortDescription'] ?? self::getDefault();
    }

    public static function getDefault(): string
    {
        return '';
    }
}