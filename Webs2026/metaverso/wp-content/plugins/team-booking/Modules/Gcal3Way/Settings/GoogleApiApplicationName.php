<?php

namespace VSHM\Modules\Gcal3Way\Settings;

use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class GoogleApiApplicationName
 */
class GoogleApiApplicationName extends GoogleSettingBase
{

    public const ID = 'googleApiApplicationName';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Input::get(__('API App Name', 'team-booking'), self::ID);
        $element->setDescription(__('Google Cloud Platform console > OAuth Consent screen > Edit App (button) > App name', 'team-booking'));

        return $element;
    }

    public static function default(): string
    {
        return '';
    }
}