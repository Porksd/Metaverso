<?php

namespace VSHM\Settings\Service;

use VSHM\Providers\Objects\Service;
use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class Picture
 *
 * @package VSHM
 */
class Picture extends ServiceSettingBase
{

    public const ID = 'picture';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Picture::get(__('Picture', 'team-booking'), self::ID);
        $element->setDescription(__('Provide a picture for the service.', 'team-booking'));

        return $element;
    }

    public static function getProcessedDefault(Service $service)
    {
        return $service->data['picture'] ?? self::getDefault();
    }

    public static function getDefault(): string
    {
        return '';
    }
}