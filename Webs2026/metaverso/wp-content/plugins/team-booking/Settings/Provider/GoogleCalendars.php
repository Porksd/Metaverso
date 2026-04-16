<?php

namespace VSHM\Settings\Provider;

use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class GoogleCalendars
 */
class GoogleCalendars extends ProviderSettingBase
{

    public const ID = 'tbk_GoogleCalendars';

    public static function getBackendElement(): ?Element_Setting
    {
        return null;
    }

    public static function default($resourceId): array
    {
        return [];
    }

}