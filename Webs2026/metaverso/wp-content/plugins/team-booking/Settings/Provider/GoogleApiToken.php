<?php

namespace VSHM\Settings\Provider;

use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class GoogleApiToken
 */
class GoogleApiToken extends ProviderSettingBase
{

    public const ID = 'tbk_GoogleAccessToken';

    public static function getBackendElement(): ?Element_Setting
    {
        return null;
    }

    public static function default($resourceId): string
    {
        return '';
    }

}