<?php

namespace VSHM\Settings\Provider;

use VSHM\Providers\ServiceProviders;
use VSHM\Settings\PropertyBase;

defined('ABSPATH') || exit;

/**
 * Implements some common setting methods.
 *
 * Defines an interface for a generic service setting.
 */
abstract class ProviderSettingBase extends PropertyBase
{
    public const CONTEXT = 'providers';

    public static function subscribe(): void
    {
        parent::subscribe();

        add_filter('vshm_default_provider_settings', static function ($defaults, $slug) {
            if ($slug === vshm()->plugin['SLUG']) {
                $defaults[ static::ID ] = static::default(NULL);
            }

            return $defaults;
        }, 10, 2);
        add_filter('tbk_provider_meta_keys', static function ($metaKeys) {
            $metaKeys[] = static::ID;

            return $metaKeys;
        });
        add_filter('tbk_provider_meta_value', static function ($value, $metaKey) {

            if ($metaKey === static::ID) {
                return static::sanitize($value, NULL);
            }

            return $value;

        }, 10, 2);
    }

    public static function onBeforeGet($resourceId): void
    {
        if (!vshm()->settings->resourceExists(self::CONTEXT, $resourceId)) {
            $provider = ServiceProviders::provideBy(['id' => $resourceId], TRUE);
            if ($provider) {
                vshm()->settings->hydrateResource(
                    $provider,
                    self::CONTEXT,
                    $resourceId
                );
            }
        }
    }
}
