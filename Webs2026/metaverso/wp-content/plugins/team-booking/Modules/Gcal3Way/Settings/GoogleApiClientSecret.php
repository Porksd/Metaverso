<?php

namespace VSHM\Modules\Gcal3Way\Settings;

use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class GoogleApiClientSecret
 */
class GoogleApiClientSecret extends GoogleSettingBase
{

    public const ID = 'googleApiClientSecret';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Input::get(__('API Client Secret', 'team-booking'), self::ID);
        $element->setDescription(__('Google Cloud Platform console > Credentials > Client ID > Edit oAuth Client (pencil icon) > Client Secret', 'team-booking'));

        return $element;
    }

    public static function default(): string
    {
        return '';
    }
}