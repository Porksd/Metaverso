<?php

namespace VSHM\Settings\Promotion;

use VSHM\Providers\Services;
use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Option;

defined('ABSPATH') || exit;

/**
 * Class AllowedServices
 */
class PromotionServices extends PromotionSettingBase
{

    public const ID = 'tbk_PromotionServices';

    public static function getBackendElement(): Element_Setting
    {
        $services = Services::provide();

        $element = \VSHM\UI\Admin\Settings_Checkboxes::get(__('Services to target', 'team-booking'), self::ID);
        $element->setDescription(__('Select the services eligible for this promotion.', 'team-booking'));

        foreach ($services as $service) {
            $option = Settings_Option::get($service->name, $service->id);
            $element->addOption($option);
        }

        return $element;
    }

    public static function default($resourceId): array
    {
        return [];
    }
}