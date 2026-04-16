<?php

namespace VSHM\Settings\Service;

use VSHM\Providers\Objects\Service;

defined('ABSPATH') || exit;

/**
 * Implements some common setting methods.
 *
 * Defines an interface for a generic service setting.
 */
abstract class ServiceSettingBase
{
    public static function subscribe(): void
    {
        add_filter('vshm_default_service_settings', static function ($defaults, $slug, $service) {
            if ($slug !== vshm()->plugin['SLUG']) {
                return $defaults;
            }
            if (!isset($defaults[ static::ID ])) {
                $defaults[ static::ID ] = static::getProcessedDefault($service);
            } else {
                $defaults[ static::ID ] = static::whitelist($defaults[ static::ID ], vshm()->plugin['VERSION']);
            }

            return $defaults;
        }, 10, 3);
    }

    /**
     * Allows defaults based on service record.
     *
     * @param Service $service
     *
     * @return mixed
     */
    public static function getProcessedDefault(Service $service)
    {
        return static::getDefault();
    }

    public static function whitelist($value, string $version)
    {
        return $value;
    }
}
