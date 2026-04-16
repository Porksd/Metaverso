<?php

namespace VSHM\Settings\Service;

defined('ABSPATH') || exit;

/**
 * Implements some common setting methods.
 *
 * Defines an interface for a generic service setting.
 *
 * @package VSHM
 */
abstract class ServicePersonalSettingBase extends ServiceSettingBase
{
    public static function subscribe(): void
    {
        add_filter('vshm_default_service_personal_settings', static function ($defaults, $slug, $service) {
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

        add_filter('vshm_ensure_service_personal_setting', static function ($value, $id, $service) {

            if ($id !== static::ID) {
                return $value;
            }

            if (NULL === $value) {
                return static::getProcessedDefault($service);
            }

            return static::whitelist($value, vshm()->plugin['VERSION']);
        }, 10, 3);
    }
}
