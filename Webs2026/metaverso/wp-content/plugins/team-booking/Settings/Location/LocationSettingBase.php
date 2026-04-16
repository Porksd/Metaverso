<?php

namespace VSHM\Settings\Location;

use VSHM\Bus\EditLocation;
use VSHM\Providers\Locations;
use VSHM\Settings\PropertyBase;

defined('ABSPATH') || exit;

/**
 * Implements some common setting methods.
 *
 * Defines an interface for a generic location setting.
 */
abstract class LocationSettingBase extends PropertyBase
{
    public const CONTEXT = 'locations';

    public static function subscribe(): void
    {
        parent::subscribe();

        add_filter('vshm_default_location_settings', static function ($defaults, $slug) {
            if ($slug === vshm()->plugin['SLUG']) {
                $defaults[ static::ID ] = static::default(NULL);
            }

            return $defaults;
        }, 10, 2);
    }

    public static function onBeforeGet($resourceId): void
    {
        if (!vshm()->settings->resourceExists(self::CONTEXT, $resourceId)) {
            $location = Locations::provideBy(['id' => $resourceId], TRUE);
            if ($location) {
                vshm()->settings->hydrateResource(
                    $location,
                    self::CONTEXT,
                    $resourceId
                );
            }
        }
    }

    public static function onSave($value, $resourceId): void
    {
        $location = Locations::provideBy(['id' => $resourceId], TRUE);
        if ($location) {
            if (array_key_exists(static::ID, $location)) {
                $location[ static::ID ] = $value;
                vshm()->bus->dispatch(new EditLocation(
                    $location['id'],
                    $location['name'],
                    $location['address'],
                    $location['status'],
                    $location['lat'],
                    $location['long'],
                    $location['capacity']
                ));
            }
        }
    }
}
