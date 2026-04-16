<?php

namespace VSHM\Settings\Provider;

use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class GoogleAccount
 */
class GoogleAccount extends ProviderSettingBase
{

    public const ID = 'tbk_GoogleAuthAccount';

    public static function getBackendElement(): ?Element_Setting
    {
        return null;
    }

    public static function default($resourceId): string
    {
        return '';
    }

}