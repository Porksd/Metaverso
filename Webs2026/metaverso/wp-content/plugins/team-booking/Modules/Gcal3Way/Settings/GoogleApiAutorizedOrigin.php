<?php

namespace VSHM\Modules\Gcal3Way\Settings;

use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Content;

defined('ABSPATH') || exit;

/**
 * Class GoogleApiAutorizedOrigin
 */
class GoogleApiAutorizedOrigin extends GoogleSettingBase
{

    public const ID = 'googleApiAuthorizedOrigin';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Informative::get(__('Google API Authorized JS Origin', 'team-booking'), self::ID);
        $element->setDescription(__('Copy/Paste this link in the Authorized JS Origin field of the Google Project', 'team-booking'));
        $element->addContent(Settings_Content::Text(get_home_url()));

        return $element;
    }

    public static function default(): string
    {
        return '';
    }
}