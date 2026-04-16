<?php

namespace VSHM\Settings\Provider;

use VSHM\UI\Admin\Alert;
use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class RestrictServices
 */
class RestrictServices extends ProviderSettingBase
{
    public const ID = 'tbk_RestrictServices';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Toggle::get(__('Restrict services', 'team-booking'), self::ID);
        $element->setDescription(__('If you want to assign this service provider to specific services, activate this setting.', 'team-booking'));
        $element->setAlert(Alert::warning(__('Please note that if this setting is active, new services will not be automatically assigned to this provider.', 'team-booking')));

        return $element;
    }

    public static function sanitize($value, $resourceId)
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public static function default($resourceId): bool
    {
        return FALSE;
    }
}