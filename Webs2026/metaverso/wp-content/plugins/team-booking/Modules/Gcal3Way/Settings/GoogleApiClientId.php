<?php

namespace VSHM\Modules\Gcal3Way\Settings;

use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class GoogleApiClientId
 */
class GoogleApiClientId extends GoogleSettingBase
{

    public const ID = 'googleApiClientId';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Input::get(__('API Client ID', 'team-booking'), self::ID);
        $element->setDescription(__('Google Cloud Platform console > Credentials > Client ID', 'team-booking'));

        return $element;
    }

    public static function default(): string
    {
        return '';
    }
}