<?php

namespace VSHM\Settings\Customer;

use VSHM\Providers\Customers;
use VSHM\Settings\PropertyBase;

defined('ABSPATH') || exit;

/**
 * Implements some common setting methods.
 *
 * Defines an interface for a generic customer setting.
 */
abstract class CustomerSettingBase extends PropertyBase
{
    public const CONTEXT = 'customers';

    public static function subscribe(): void
    {
        parent::subscribe();

        add_filter('vshm_default_customer_settings', static function ($defaults, $slug) {
            if ($slug === vshm()->plugin['SLUG']) {
                $defaults[ static::ID ] = static::default(NULL);
            }

            return $defaults;
        }, 10, 2);
    }

    public static function onBeforeGet($resourceId): void
    {
        if (!vshm()->settings->resourceExists(self::CONTEXT, $resourceId)) {
            $customer = Customers::provideBy(['id' => $resourceId], TRUE);
            if ($customer) {
                vshm()->settings->hydrateResource(
                    $customer,
                    self::CONTEXT,
                    $resourceId
                );
            }
        }
    }
}
