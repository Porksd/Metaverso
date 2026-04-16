<?php

namespace VSHM\Settings\Service;

use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Dependency;

defined('ABSPATH') || exit;

/**
 * Class ShowProviderUrl
 *
 * @package VSHM
 */
class ShowProviderUrl extends ServiceSettingBase
{
    public const ID = 'showProviderUrl';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Toggle::get(__('Show provider profile page link', 'team-booking'), self::ID);
        $element->setDescription(__("When enabled, the provider name will be displayed as a hyperlink that leads to the provider's profile page. To set the profile page for each provider, navigate to WordPress > Users > Edit > Website.", 'team-booking'));

        $element->addDependency(Settings_Dependency::TRUTHY(ShowProvider::ID));

        return $element;
    }

    public static function getDefault(): bool
    {
        return FALSE;
    }

    public static function whitelist($value, string $version)
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}