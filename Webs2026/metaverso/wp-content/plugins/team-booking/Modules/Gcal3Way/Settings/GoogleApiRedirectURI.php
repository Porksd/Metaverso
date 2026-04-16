<?php

namespace VSHM\Modules\Gcal3Way\Settings;

use VSHM\Modules\Gcal2Ways;
use VSHM\REST_Controller;
use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Content;

defined('ABSPATH') || exit;

/**
 * Class GoogleApiRedirectURI
 */
class GoogleApiRedirectURI extends GoogleSettingBase
{

    public const ID = 'googleApiRedirectUri';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Informative::get(__('Google API Redirect URI', 'team-booking'), self::ID);
        $element->setDescription(__('Copy/Paste this link in the Redirect URI field of the Google Project', 'team-booking'));
        $element->addContent(Settings_Content::Text(REST_Controller::get_root_rest_url() . Gcal2Ways::$route_path . 'oauth'));

        return $element;
    }

    public static function default(): string
    {
        return '';
    }
}