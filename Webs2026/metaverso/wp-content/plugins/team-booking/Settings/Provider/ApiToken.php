<?php

namespace VSHM\Settings\Provider;

use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class ApiToken
 */
class ApiToken extends ProviderSettingBase
{

    public const ID = 'tbk_ApiAccessToken';

    public static function getBackendElement(): ?Element_Setting
    {
        return null;
    }

    public static function default($resourceId): string
    {
        return '';
    }

}