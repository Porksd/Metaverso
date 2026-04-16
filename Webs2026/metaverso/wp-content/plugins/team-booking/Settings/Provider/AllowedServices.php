<?php

namespace VSHM\Settings\Provider;

use VSHM\Providers\Services;
use VSHM\Tools;
use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Dependency;
use VSHM\UI\Admin\Settings_Option;

defined('ABSPATH') || exit;

/**
 * Class AllowedServices
 */
class AllowedServices extends ProviderSettingBase
{

    public const ID = 'tbk_AllowedServices';

    public static function getBackendElement(): Element_Setting
    {
        $services = Services::provide();

        $element = \VSHM\UI\Admin\Settings_Checkboxes::get(__('Allowed services', 'team-booking'), self::ID);
        $element->setDescription(__('Select the services to which this provider is restricted.', 'team-booking'));

        foreach ($services as $service) {
            $option = Settings_Option::get($service->name, $service->id);
            $element->addOption($option);
        }

        $element->addDependency(Settings_Dependency::TRUTHY(RestrictServices::ID));

        return $element;
    }

    public static function sanitize($value, $resourceId): array
    {
        if (is_array($value) && Tools::array_is_assoc($value)) {
            $value = array_keys($value);
        }

        return empty($value) ? [] : (array)$value;
    }

    public static function default($resourceId): array
    {
        return [];
    }
}