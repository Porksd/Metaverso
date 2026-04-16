<?php

namespace VSHM\Settings;

use VSHF\Config\Dependency;

defined('ABSPATH') || exit;

/**
 * Implements some common setting methods.
 *
 * Defines an interface for a generic backend setting.
 */
abstract class PropertyBase implements \VSHF\Config\PropertyObserverInterface, Setting
{
    public static function subscribe(): void
    {
        vshm()->settings->registerPropertyObserver(static::ID, static::class, static::CONTEXT);
    }

    public static function onSave($value, $resourceId): void
    {
        // TODO: Implement onSave() method.
    }

    public static function onGet($value, $resourceId): void
    {
        // TODO: Implement onGet() method.
    }

    public static function onBeforeGet($resourceId): void
    {
        // TODO: Implement onBeforeGet() method.
    }

    public static function sanitize($value, $resourceId)
    {
        return $value;
    }

    public static function validate($value, $resourceId): bool
    {
        return TRUE;
    }

    public static function dependencies($resourceId): ?Dependency
    {
        return NULL;
    }
}
